# 🎨 Inventory Management System - Frontend

React-based frontend application for the multi-branch inventory management system with professional UI/UX.

## 🚀 Quick Start

```bash
# Install dependencies (already done)
npm install

# Start development server
npm run dev

# Server runs on: http://localhost:3000
```

### Demo Login Credentials
```
Email: admin@inventory.com
Password: password
```

## 📊 Current Status: Phase 1 Complete ✅

### ✅ Completed Features
- Professional Login Page with validation
- Dashboard with KPI cards
- 9 reusable components (Atomic Design)
- Authentication flow (login/logout)
- Protected routes
- Design system with Tailwind CSS
- RTL support for Arabic
- Responsive design

### 📁 Component Library
```jsx
// Atoms (Basic Components)
import { Button, Input, Card, Badge, Alert, Spinner } from '@/components/atoms';

// Molecules (Combined Components)
import { FormField, SearchBar, StatCard } from '@/components/molecules';
```

## 🛠️ Tech Stack

- **React 18.3** - UI Library
- **Vite 5.4** - Build Tool & Dev Server (⚡ Lightning fast HMR)
- **React Router 6** - Client-side Routing
- **Axios** - HTTP Client with interceptors
- **Tailwind CSS 3.4** - Utility-first CSS framework
- **Lucide React** - Beautiful icon library
- **Cairo Font** - Arabic-optimized typography

## 📁 Project Structure

```
frontend/
├── src/
│   ├── components/       # Reusable UI components (Atomic Design)
│   │   ├── atoms/        # Button, Input, Card, Badge, Alert, Spinner
│   │   ├── molecules/    # FormField, SearchBar, StatCard
│   │   └── ProtectedRoute.jsx
│   ├── contexts/         # React Context (AuthContext)
│   ├── pages/           # Page components (Login, Dashboard)
│   ├── utils/           # Utilities & API client
│   ├── App.jsx          # Main App component with routing
│   ├── main.jsx         # Entry point
│   └── index.css        # Design system & Tailwind
├── docs/                # Documentation
│   ├── UI-UX-DEVELOPMENT-PLAN.md
│   └── PHASE-1-COMPLETE.md
├── public/              # Static assets
├── .env                 # Environment variables
├── vite.config.js       # Vite configuration
└── tailwind.config.js   # Tailwind configuration
```

## 🛠️ Setup & Installation

### Prerequisites
- Node.js 18+ 
- npm or yarn

### Install Dependencies
```bash
npm install
```

### Environment Variables
Create `.env` file in the root:
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME="نظام إدارة المخزون"
```

## 🏃‍♂️ Running the Application

### Development Server
```bash
npm run dev
```
Access at: http://localhost:3000

### Build for Production
```bash
npm run build
```

### Preview Production Build
```bash
npm run preview
```

## 🔌 API Integration

### Base Configuration
```javascript
// src/utils/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});
```

### Authentication
Uses Laravel Sanctum token-based authentication:
```javascript
// Login
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

// Response
{
  "data": {
    "token": "1|...",
    "user": { ... }
  }
}
```

## 📋 Available Features

### ✅ Implemented
- [x] Project Setup with Vite & React
- [x] Tailwind CSS Configuration
- [x] API Client Setup
- [x] Authentication Context
- [x] Basic Routing Structure

### 🚧 In Progress
- [ ] Login Page UI
- [ ] Dashboard Layout
- [ ] Protected Routes
- [ ] Branch Selector Component

### 📝 Planned
- [ ] Products Management UI
- [ ] Issue Vouchers UI
- [ ] Return Vouchers UI
- [ ] Reports & Analytics
- [ ] Customer Management
- [ ] Stock Management

## 🎨 Design System

### Colors
- Primary: Blue (#3B82F6)
- Success: Green (#10B981)
- Warning: Orange (#F59E0B)
- Danger: Red (#EF4444)
- Gray Scale: Tailwind default

### Typography
- Font: Cairo (Arabic optimized)
- Sizes: Tailwind default scale

### RTL Support
Full Right-to-Left support for Arabic language:
```html
<html lang="ar" dir="rtl">
```

## 🔐 Authentication Flow

1. User enters credentials on `/login`
2. POST to `/api/v1/auth/login`
3. Receive token and user data
4. Store token in localStorage
5. Set axios Authorization header
6. Redirect to `/dashboard`
7. Protected routes check token validity

## 🗂️ State Management

### Auth State (Context API)
```javascript
const { user, token, login, logout, isAuthenticated } = useAuth();
```

### Global State (Zustand - optional)
For complex state like:
- Current branch selection
- Cart/Form data
- UI state

## 📱 Responsive Design

- Mobile-first approach
- Breakpoints: sm, md, lg, xl, 2xl (Tailwind)
- Touch-friendly UI elements
- Optimized for tablets and desktop

## 🔧 Development Tools

### VS Code Extensions (Recommended)
- ES7+ React/Redux snippets
- Tailwind CSS IntelliSense
- ESLint
- Prettier

### Browser Extensions
- React Developer Tools
- Redux DevTools (if using Redux)

## 📚 API Documentation

Full API documentation available in backend:
- `docs/API-DOCUMENTATION.md`
- Swagger/OpenAPI (if available)

## 🧪 Testing

```bash
# Unit tests (Coming soon)
npm run test

# E2E tests (Coming soon)
npm run test:e2e
```

## 📦 Build & Deployment

### Production Build
```bash
npm run build
# Output: dist/
```

### Deploy to:
- Vercel
- Netlify
- GitHub Pages
- Any static hosting

### Environment Variables for Production
```env
VITE_API_BASE_URL=https://api.yourdomain.com/api/v1
```

## 🤝 Contributing

1. Follow React best practices
2. Use functional components & hooks
3. Implement proper error handling
4. Write clean, readable code
5. Add comments for complex logic

## 📄 License

Proprietary - All rights reserved

## 👨‍💻 Development Status

**Current Phase:** Initial Setup ✅  
**Next Phase:** Authentication UI & Dashboard Layout

---

**Last Updated:** 2025-10-12  
**Version:** 1.0.0
