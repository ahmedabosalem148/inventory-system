# API Documentation

## Base URL
```
Development: http://localhost:8000/api/v1
Production: https://your-domain.com/api/v1
```

## Authentication
All API endpoints require authentication using Laravel Sanctum.

### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

Response:
```json
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com"
  },
  "token": "1|abc123..."
}
```

### Logout
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

---

## Endpoints

### Products
- `GET /api/v1/products` - List all products
- `GET /api/v1/products/{id}` - Get single product
- `POST /api/v1/products` - Create product
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product

### Customers
- `GET /api/v1/customers` - List all customers
- `GET /api/v1/customers/{id}` - Get single customer
- `POST /api/v1/customers` - Create customer
- `PUT /api/v1/customers/{id}` - Update customer
- `DELETE /api/v1/customers/{id}` - Delete customer

### Branches
- `GET /api/v1/branches` - List all branches
- `GET /api/v1/branches/{id}` - Get single branch
- `POST /api/v1/branches` - Create branch
- `PUT /api/v1/branches/{id}` - Update branch
- `DELETE /api/v1/branches/{id}` - Delete branch

### Vouchers
- `GET /api/v1/issue-vouchers` - List issue vouchers
- `POST /api/v1/issue-vouchers` - Create issue voucher
- `GET /api/v1/return-vouchers` - List return vouchers
- `POST /api/v1/return-vouchers` - Create return voucher

### Reports
- `GET /api/v1/reports/inventory` - Inventory report
- `GET /api/v1/reports/customer-statement/{id}` - Customer statement
- `GET /api/v1/reports/sales-summary` - Sales summary

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 500 Server Error
```json
{
  "message": "Server Error",
  "error": "Error details..."
}
```

---

## Rate Limiting
- 60 requests per minute per IP
- Headers included in response:
  - `X-RateLimit-Limit`: 60
  - `X-RateLimit-Remaining`: 59
  - `Retry-After`: 60 (when exceeded)

---

## Pagination
List endpoints support pagination:

```http
GET /api/v1/products?page=1&per_page=15
```

Response:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "http://localhost/api/v1/products?page=1",
    "last": "http://localhost/api/v1/products?page=7",
    "prev": null,
    "next": "http://localhost/api/v1/products?page=2"
  }
}
```

---

## Coming Soon
- Detailed endpoint documentation
- Request/response examples
- Postman collection
- OpenAPI/Swagger specification
