
# Prompt to Generate the Inspectable Application Frontend

Create a comprehensive web application called "Inspectable" for scheduling and managing asset inspections. The application should be built with Next.js (using the App Router), TypeScript, and styled with Tailwind CSS and ShadCN/UI components.

The application must be architected around a central `DataContext` using React Context for all client-side state management, which will handle all interactions with a Firebase backend.

**1. Core Entities & Data Model:**

The app will manage four primary entities: `Inspections`, `Locations`, `Departments`, and `AppUsers`.

*   **`AppUser`**: Represents an application user.
    *   **Fields**: `id` (Firebase UID), `name`, `email`, `phone`, `departmentId`, `photoURL`, `status` ('Verified' or 'Unverified'), and `role` (an array of strings).
    *   **Roles**: The `role` field can contain 'Admin', 'Asset Officer', 'Auditor', or 'Viewer'.
*   **`Department`**: An organizational unit.
    *   **Fields**: `id`, `name`, `acronym`.
*   **`Location`**: A physical place to be inspected.
    *   **Fields**: `id`, `name`, `departmentId`, `supervisor`, `contactNumber`.
*   **`Inspection`**: A scheduled inspection event.
    *   **Fields**: `id`, `locationId`, `departmentId`, `locationName`, `supervisor`, `contactNumber`, `date`, `auditor1` (string name), `auditor2` (string name), `status` ('Pending' or 'Complete'). This includes denormalized data from `Location` for efficiency.

**2. Authentication & User Management:**

*   **Login Page**: The root page (`/`) should be a professional login screen allowing users to sign in with Email/Password or Google. It should include "Forgot Password" and "Sign Up" functionality.
*   **Sign-up Flow**: New users who sign up will have an `AppUser` document created with a status of 'Unverified'. They cannot log in until an Admin changes their status to 'Verified'.
*   **User Profile Page**: Authenticated users must be able to view and edit their own profile information (name, phone, department) on a dedicated `/dashboard/profile` page.
*   **Admin User Management**: Users with the 'Admin' role must have access to a `/dashboard/users` page where they can:
    *   View a table of all application users.
    *   Edit a user's roles, department, and other details.
    *   "Verify" a new user, changing their status to 'Verified'.
    *   Delete a user, which must remove both their Firestore record and their Firebase Authentication account.

**3. Application Layout & Navigation:**

*   **Dashboard Layout**: Once logged in, the user should be directed to a dashboard layout. This layout must feature a collapsible sidebar on the left and a main content area with a header.
*   **Sidebar Navigation**: The sidebar should be role-aware, showing navigation items only to users with the appropriate permissions.
    *   **Overview (`/dashboard/overview`)**: Visible to all.
    *   **Schedule (`/dashboard/schedule`)**: Visible to 'Admin' and 'Auditor'.
    *   **Locations (`/dashboard/locations`)**: Visible to 'Admin' and 'Asset Officer'.
    *   **Reports (Collapsible Menu)**:
        *   **Inspection Status (`/dashboard/report/inspection-status`)**: Visible to 'Admin' and 'Asset Officer'.
    *   **Settings (Collapsible Menu)**:
        *   **Departments (`/dashboard/departments`)**: Visible to 'Admin'.
        *   **Users (`/dashboard/users`)**: Visible to 'Admin'.
*   The sidebar should also display the current user's avatar, name, roles, and a logout button.

**4. Core Feature Pages (CRUD Functionality):**

All data management pages should use ShadCN UI components like `Card`, `Table`, `Dialog`, `Form`, and `Button` for a consistent and modern look and feel.

*   **Departments Page (`/dashboard/departments`):** Full CRUD functionality for managing departments, presented in a table with add, edit, and delete actions opening a dialog form.
*   **Locations Page (`/dashboard/locations`):** Full CRUD for locations. The form should include a dropdown to select a department. The table should be filterable by department. When a location is created, a corresponding "Pending" inspection should be automatically created for it.
*   **Schedule Page (`/dashboard/schedule`):**
    *   Display all inspections in a sortable and filterable table.
    *   Allow an 'Auditor' or 'Admin' to assign themselves to an open auditor slot (`auditor1` or `auditor2`).
    *   Allow editing the inspection `date` via a popover calendar.
*   **Inspection Status Page (`/dashboard/report/inspection-status`):**
    *   Display a table of inspections, filterable by department.
    *   Allow an 'Admin' or 'Asset Officer' to toggle the `status` of an inspection between 'Pending' and 'Complete'.
*   **Overview Page (`/dashboard/overview`):**
    *   Display a read-only view of the upcoming inspection schedule.
    *   Include summary cards for "Auditor Analysis" (showing top auditors by inspection count) and "Inspection Status" (showing department-level completion progress bars).

**5. Backend Integration (Firebase):**

*   **Central Data Context (`DataContext`)**: Create a `DataProvider` that wraps the application. This provider will use custom hooks (`useCollection`, `useUser`) to fetch all necessary data (inspections, locations, departments, users) from Firestore in real-time. It will provide this data and functions to modify it (e.g., `setInspections`, `updateUserProfile`) to any component in the app.
*   **Firebase Admin API Routes**: For secure admin actions (deleting a user, setting roles/claims), create server-side Next.js API Route Handlers.
    *   One route for deleting a user (`/api/admin/users/[uid]`).
    *   One route for setting custom auth claims (`/api/admin/users/[uid]/set-claims`).
    *   These routes must be secured by verifying the incoming ID token has an `admin: true` custom claim. The client-side `DataContext` will be responsible for calling these endpoints and including the user's ID token for authorization.
