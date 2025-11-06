import axios from 'axios';

// Adjust to your Apache path. If you copy php-backend/public to C:\\wamp64\\www\\inspectable-api,
// this becomes http://localhost/inspectable-api/api
const BASE_URL = (import.meta.env.VITE_API_BASE as string) || 'http://localhost/inspectable-api/api';

export const api = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' }
});

export type Department = { id?: number; name: string; acronym?: string };
export type Location = { id?: number; name: string; department_id: number; supervisor?: string; contact_number?: string };
export type User = { id: string; name: string; email: string; phone?: string; department_id?: number; photo_url?: string; status?: 'Verified'|'Unverified'; roles?: string[] };
export type Inspection = { id?: number; location_id: number; inspection_date: string; status?: 'Pending'|'Complete'; auditor1_id?: string|null; auditor2_id?: string|null };
