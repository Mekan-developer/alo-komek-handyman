# Technical Specification: Handyman Service System

## 1. General Information

**Purpose**: A system for clients to search and request handyman services, with the ability for administrators to assign masters to orders.

**System Components**:
- Client mobile app (Flutter)
- Master mobile app (Flutter)
- Web admin panel (Laravel + Vue 3)
- Backend API (Laravel)
- WebSocket server (Laravel Reverb)
- Database (MySQL)
- Maps: OpenStreetMap

**Mobile Platforms**: Android and iOS.

---

## 2. Interface Languages

**Mobile apps (client & master)**:
- Turkmen — primary
- Russian — secondary

**Admin panel**:
- Turkmen
- Russian

---

## 3. Roles & Permissions

### Administrator
- Manage masters (activate, deactivate, set access expiry date)
- Manage service categories
- Manage cities
- Assign masters to orders
- Create orders manually
- View masters on map
- Set payment model per master
- View and manage master payouts

### Master (Handyman)
- Register via mobile app
- Login via phone number with OTP confirmation
- Fill in profile (first name, last name, phone)
- Select service categories
- Receive orders
- Accept orders (only when access is active)
- View personal order history
- Appear on map within their city

### Client
- Select city on registration or change it in profile
- Submit an order
- Select service category
- Describe the problem
- Attach photos of the problem
- Track order status

---

## 4. Geography
- System operates within individual cities
- Client selects a city on registration/login
- City list is managed in the admin panel
- Masters and orders are tied to the selected city
- Map shows only masters from the client's current city

---

## 5. Service Categories

Examples:
- Electrical
- Plumbing
- Home appliance repair
- Washing machines
- And others

Administrator can create, edit, activate, and deactivate categories.

---

## 6. Master Access Logic
- Master receives system access for a limited period (in days)
- Access period is set by the administrator
- When access expires, the master:
  - Cannot accept new orders
  - Is removed from the client catalog
  - Does not appear on the map

---

## 7. Master Payment Models

Administrator assigns exactly one payment model per master:
- **Percentage** — % of order amount (e.g. 35%)
- **Fixed per job** — fixed amount per completed job (e.g. 200 manat)
- **Salary** — fixed monthly payment
- **Salary + Percentage** — fixed monthly + % of order amount

**Payment Rules**:
- Selected model is applied automatically when an order is completed
- After work confirmation, amount is calculated and credited to master's balance
- Administrator can change the payment model at any time
- Administrator can reset master's balance to zero after payout

---

## 8. Order Logic

### Client Order Creation
- Select city
- Select service category
- Describe the problem in detail
- Provide contact phone number
- Attach up to 4 photos of the problem

### Order Processing
- Order arrives in admin panel → administrator assigns a master

### Master Work Flow
1. Master arrives at the location
2. Determines the final cost of the work (may differ from initial estimate)
3. Agrees on the cost with the client
4. Calls the operator and reports the final amount
5. Completes the work
6. Uploads Before & After photos for each individual task performed

**Example**: master fixed a tap and replaced a hose — these are two separate tasks. Each requires a Before photo and an After photo (4 photos total).

### Order Statuses
- `pending` — new
- `assigned` — master assigned
- `in_progress` — work in progress
- `completed` — completed
- `cancelled` — cancelled

---

## 9. UI/UX Visual Style

Both mobile apps (client and master) should follow the JustLife app as a design reference — modern, clean, minimalist, and user-friendly with emphasis on simplicity and quality visual presentation.

---

## 10. Realtime Updates

System must support real-time updates for:
- New order submitted
- Master assigned to order
- Order status changed

---

## 11. Restrictions
- Master can only work while access is active
- One master can provide services in multiple categories
- Work geography is limited to the selected city

---

## 12. Photo Upload
- All photos are uploaded via Queue (Job) — never synchronously
- After upload, photos are automatically converted to WebP format
- Original file is deleted after conversion
- User does not wait — upload happens in the background
- Upload status is shown in UI (pending → done)

---

## 13. Admin Notifications
- When a client submits a new order:
  - A sound alert plays in the admin panel
  - A toast notification appears via useNotificationStore
  - Implemented via Laravel Reverb (WebSocket)
  - Sound plays only when the browser tab is active (Page Visibility API)
  - Sound file stored at `public/sounds/new-order.mp3`