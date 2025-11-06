# Inspectable App: System Overview

This document provides a comprehensive overview of the architecture, data model, and functionality of the "Inspectable" web application.

## 1. Core Technology Stack

The application is built using a modern, robust technology stack designed for performance, scalability, and maintainability.

- **Framework**: **Next.js (with App Router)** is the foundation. It's a React framework that enables server-side rendering and powerful features like Server Components for optimized performance.
- **Language**: **TypeScript** is used for the entire codebase. It adds static typing to JavaScript, which helps prevent common errors and improves code quality.
- **UI Components**: **ShadCN/UI** provides a collection of beautifully designed, accessible, and reusable React components (e.g., Buttons, Cards, Forms, Tables). These components are located in `src/components/ui`.
- **Styling**: **Tailwind CSS** is a utility-first CSS framework used for all styling. It allows for rapid UI development directly within the JSX. The global styles and theme variables are configured in `src/app/globals.css`.
- **Backend & Database**: **Firebase** serves as the complete backend.
    - **Firebase Authentication**: Manages all user-related functions, including sign-up, sign-in, session management, and password resets.
    - **Firestore**: A NoSQL, document-based database used to store all application data.

## 2. Project Structure

The project is organized to separate concerns and make navigation intuitive.

- `src/app/`: The heart of the Next.js application, using the App Router.
    - `layout.tsx`: The root layout for the entire application.
    - `page.tsx`: The main landing/login page.
    - `dashboard/`: Contains all pages and components for the authenticated user experience. Each subfolder represents a route (e.g., `src/app/dashboard/schedule/` corresponds to the `/dashboard/schedule` URL).
- `src/components/ui/`: Reusable UI components from ShadCN/UI.
- `src/context/`:
    - `data-context.tsx`: The most critical file for client-side state management. It provides a React Context (`DataProvider`) that centralizes all data fetching, state management, and interaction with the Firebase backend.
- `src/firebase/`: Contains all Firebase-related configuration, initialization, and custom hooks.
- `src/lib/`: Contains utility functions (`utils.ts`) and TypeScript type definitions (`types.ts`).
- `src/app/api/admin/`: Contains server-side Next.js API Route Handlers that use the **Firebase Admin SDK** for secure, privileged backend operations.

## 3. Data Model (Firestore Collections)

All application data is stored in Firestore. The path to these collections follows the pattern `artifacts/{appId}/public/data/{collectionName}`, where `appId` is a static identifier for this application (`inspect-app`).

| Collection    | Purpose                                                                                                 | Key Fields                                                                                             |
| :------------ | :------------------------------------------------------------------------------------------------------ | :----------------------------------------------------------------------------------------------------- |
| `app_users`   | Stores application-specific user profiles, including their roles and status.                            | `id` (matches Auth UID), `name`, `email`, `role` (Array), `status` ('Verified'/'Unverified'), `departmentId` |
| `departments` | Defines the different departments within the organization for categorizing locations.                   | `id`, `name`, `acronym`                                                                                |
| `locations`   | Represents a physical location to be inspected.                                                         | `id`, `name`, `departmentId`, `supervisor`, `contactNumber`                                            |
| `inspections` | Represents a scheduled inspection for a location. Contains denormalized data for easy access.           | `id`, `locationId`, `locationName`, `date`, `auditor1`, `auditor2`, `status` ('Pending'/'Complete')      |

**Denormalization**: The `inspections` collection contains copies of data from other collections (like `locationName` and `supervisor` from `locations`). This is done intentionally to make reading and displaying inspection schedules faster and more efficient, as it avoids the need for extra database lookups.

## 4. Key Workflows

### 4.1. Authentication and Data Loading

1.  **Landing Page**: A new user arrives at the root page (`src/app/page.tsx`), which serves as the login screen.
2.  **Sign-In/Sign-Up**:
    *   The user signs in (with Google or email/password) or signs up for a new account.
    *   All these actions are handled by functions within the `DataProvider` (`src/context/data-context.tsx`), which call the underlying Firebase Authentication SDK methods.
    *   Upon a successful sign-in, Firebase issues an **ID Token** that proves the user's identity.
    *   If a new user signs up, an `app_users` document is created for them in Firestore with a default `status` of 'Unverified'.
3.  **Data Hydration**:
    *   Once a user is authenticated, the `DataProvider` fetches all necessary data (inspections, locations, departments, and all user profiles) from Firestore in real-time using the `useCollection` hook.
    *   This data is loaded into the application's central state.
4.  **Session Management**: The `useUser` hook listens for auth state changes. When a user is logged in, their profile is fetched from the `app_users` collection and stored in the `currentUser` state variable.
5.  **Redirection**:
    *   If `currentUser` exists, the user is automatically redirected to the `/dashboard/overview` page.
    *   If `currentUser` is null, any attempt to access a `/dashboard/*` page results in a redirect to the login page.

### 4.2. Admin User Management

This workflow demonstrates how the client and server work together for secure operations.

1.  **Admin Navigates to Users Page**: An admin user, whose `currentUser.role` includes 'Admin', navigates to `/dashboard/users`.
2.  **UI Displays Users**: The `UserClientPage` (`src/app/dashboard/users/client.tsx`) gets the list of all `appUsers` from the `DataContext` and displays them in a table.
3.  **Admin Deletes a User**:
    *   The admin clicks the "Delete" button for a specific user.
    *   This triggers the `handleDeleteUser` function in `data-context.tsx`.
    *   **Crucially, this function does NOT delete the user directly.**
4.  **Client Calls Secure API Route**:
    *   The `handleDeleteUser` function first gets the current admin's **ID Token** from Firebase Auth using `auth.currentUser.getIdToken()`.
    *   It then makes a `DELETE` request to the server-side API route: `/api/admin/users/[uid]`. The ID Token is sent in the `Authorization` header.
5.  **Server-Side Verification and Deletion**:
    *   The API route on the server receives the request.
    *   It uses the **Firebase Admin SDK** to verify the ID token. It checks if the token contains the `admin: true` custom claim.
    *   If verification passes, the Admin SDK proceeds to:
        1.  Delete the user's document from the `app_users` collection in Firestore.
        2.  Delete the user's account from Firebase Authentication.
    *   If verification fails, the server returns an "Unauthorized" error, and nothing is deleted.
6.  **UI Updates**: The client-side `DataProvider`, which has a real-time listener on the `app_users` collection, automatically receives the update from Firestore and removes the deleted user from its state, causing the UI to update instantly.

### 4.3. Data and State Management Core

The application uses a centralized data management pattern, which is key to its operation.

1.  **`DataProvider`**: This provider, located in `src/context/data-context.tsx`, wraps the entire application.
2.  **Data Fetching**: Inside the `DataProvider`, the `useCollection` hook subscribes to real-time updates from all major Firestore collections.
3.  **State Storage**: The data fetched from Firestore is placed into React state variables (`inspections`, `locations`, `departments`, `appUsers`).
4.  **Context Sharing**: These state variables and functions to modify them (`setInspections`, etc.) are exposed to the rest of the application through the `DataContext`.
5.  **Component Access**: Any component can access the data and functions by using the `useDataContext()` hook. For example, `src/app/dashboard/schedule/client.tsx` uses this hook to get the list of inspections to display.
6.  **Optimistic Updates**: When a user performs an action (e.g., assigning an auditor), the component calls a context function (like `setInspections`). This function immediately updates the local React state, causing the UI to re-render instantly for a responsive feel, while simultaneously sending the update operation to Firestore in the background to persist the change.
