# MyCanopy API Guide for External Applications

## Overview

This guide explains how external applications (mobile apps, web apps, or third-party services) can authenticate and consume the MyCanopy API at `https://mycanopy.verbeek.ug/api/`.

---

## Base URL

```
https://mycanopy.verbeek.ug/api/
```

---

## Authentication

MyCanopy uses **Laravel Sanctum** for API authentication. External applications authenticate via **Personal Access Tokens** using a simple login flow.

### Authentication Flow

1. **Login** - Exchange user credentials for an access token
2. **Use Token** - Include the token in the `Authorization` header for all authenticated requests
3. **Manage Tokens** - Optional: Revoke tokens when needed

---

## Authentication Endpoints

### POST /auth/login - Get Access Token

**Description:** Authenticate with user credentials and receive a personal access token.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/auth/login
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body (JSON):**
```json
{
    "email": "user@example.com",
    "password": "your-password",
    "device_name": "my-mobile-app"
}
```

**Request Body (x-www-form-urlencoded):**
```
email=user@example.com&password=your-password&device_name=my-mobile-app
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | User's email address |
| `password` | string | Yes | User's password |
| `device_name` | string | No | Identifier for the device/app. Defaults to "external-app" |

**Response (Success - 200 OK):**
```json
{
    "token": "1|abc123def456...",
    "token_type": "Bearer",
    "expires_at": "2026-08-12T10:00:00.000000Z",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "seller"
    }
}
```

**Response (Error - 422 Unprocessable Entity):**
```json
{
    "message": "The provided credentials are incorrect.",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

---

### GET /auth/me - Get Current User (Authenticated)

**Description:** Get information about the authenticated user.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/auth/me
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Response (Success - 200 OK):**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "seller",
    "corporation_id": null,
    "email_verified_at": "2026-01-15T10:00:00.000000Z",
    "created_at": "2026-01-15T10:00:00.000000Z",
    "updated_at": "2026-01-15T10:00:00.000000Z"
}
```

**Response (Error - 401 Unauthorized):**
```json
{
    "message": "Unauthenticated."
}
```

---

### GET /auth/me/detailed - Get Detailed User with Token Info (Authenticated)

**Description:** Get detailed user information including current token details.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/auth/me/detailed
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Response (Success - 200 OK):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "seller",
        "corporation_id": null,
        "email_verified_at": "2026-01-15T10:00:00.000000Z",
        "created_at": "2026-01-15T10:00:00.000000Z",
        "updated_at": "2026-01-15T10:00:00.000000Z"
    },
    "token": {
        "name": "my-mobile-app",
        "created_at": "2026-08-11T10:00:00.000000Z",
        "last_used_at": "2026-08-11T10:30:00.000000Z",
        "expires_at": null
    }
}
```

---

### POST /auth/logout - Revoke Current Token (Authenticated)

**Description:** Logout by revoking the current access token.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/auth/logout
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Response (Success - 200 OK):**
```json
{
    "message": "Successfully logged out and token revoked."
}
```

---

### POST /auth/tokens/revoke - Revoke Specific Token (Authenticated)

**Description:** Revoke a specific token by its name.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/auth/tokens/revoke
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "token_name": "my-mobile-app"
}
```

**Response (Success - 200 OK):**
```json
{
    "message": "Token revoked successfully."
}
```

---

### POST /auth/tokens/revoke-all - Revoke All Tokens (Authenticated)

**Description:** Revoke all access tokens for the authenticated user.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/auth/tokens/revoke-all
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Response (Success - 200 OK):**
```json
{
    "message": "All tokens revoked successfully."
}
```

---

## API Endpoints

### Public Endpoints (No Authentication Required)

These endpoints are accessible without authentication.

#### GET /properties - List Properties

**Description:** Get a paginated list of published properties with optional filtering.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/properties
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `district` | string | Filter by district (e.g., "Kampala") |
| `city` | string | Filter by city |
| `location` | string | Filter by location |
| `listing_type` | string | Filter by listing type: `rent` or `sale` |
| `property_type` | string | Filter by property type (e.g., "House", "Apartment") |
| `bedrooms` | integer | Filter by number of bedrooms |
| `bathrooms` | integer | Filter by number of bathrooms |
| `min_price` | integer | Minimum price in UGX |
| `max_price` | integer | Maximum price in UGX |
| `per_page` | integer | Number of items per page (1-100, default: 15) |
| `owned` | boolean | Only show properties owned by authenticated user (requires auth) |

**Example Request:**
```
GET https://mycanopy.verbeek.ug/api/properties?district=Kampala&listing_type=sale&bedrooms=3&per_page=10
```

**Response (Success - 200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Beautiful House in Kampala",
            "description": "Spacious 3 bedroom house with garden",
            "price_ugx": 500000000,
            "price_currency": "UGX",
            "listing_type": "sale",
            "property_type": "House",
            "bedrooms": 3,
            "bathrooms": 2,
            "district": "Kampala",
            "city": "Kampala",
            "address": "Plot 123, Spring Hills",
            "latitude": 0.3166,
            "longitude": 32.5833,
            "status": "published",
            "is_visible": true,
            "user": {
                "id": 1,
                "name": "John Doe"
            },
            "images": [
                {
                    "id": 1,
                    "path": "/storage/images/image1.jpg",
                    "sort_order": 0
                }
            ],
            "created_at": "2026-08-11T10:00:00.000000Z",
            "updated_at": "2026-08-11T10:00:00.000000Z"
        }
    ],
    "links": {
        "first": "https://mycanopy.verbeek.ug/api/properties?page=1",
        "last": "https://mycanopy.verbeek.ug/api/properties?page=5",
        "prev": null,
        "next": "https://mycanopy.verbeek.ug/api/properties?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "links": [...],
        "path": "https://mycanopy.verbeek.ug/api/properties",
        "per_page": 10,
        "to": 10,
        "total": 50
    }
}
```

---

#### GET /properties/{id} - Get Single Property

**Description:** Get details for a specific property.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/properties/1
```

**Note:** Only published and visible properties are accessible without authentication. Property owners can access their own unpublished properties when authenticated.

**Response (Success - 200 OK):**
```json
{
    "id": 1,
    "title": "Beautiful House in Kampala",
    "description": "Spacious 3 bedroom house with garden",
    "price_ugx": 500000000,
    "price_currency": "UGX",
    "listing_type": "sale",
    "property_type": "House",
    "bedrooms": 3,
    "bathrooms": 2,
    "district": "Kampala",
    "city": "Kampala",
    "address": "Plot 123, Spring Hills",
    "latitude": 0.3166,
    "longitude": 32.5833,
    "status": "published",
    "is_visible": true,
    "published_at": "2026-08-11T10:00:00.000000Z",
    "user": {
        "id": 1,
        "name": "John Doe"
    },
    "images": [
        {
            "id": 1,
            "path": "/storage/images/image1.jpg",
            "sort_order": 0
        }
    ],
    "created_at": "2026-08-11T10:00:00.000000Z",
    "updated_at": "2026-08-11T10:00:00.000000Z"
}
```

**Response (Error - 404 Not Found):**
```json
{
    "message": "No query results for model [App\\Models\\Property] 999"
}
```

---

#### GET /locations - Get Available Locations

**Description:** Get list of districts and cities for filtering.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/locations
```

**Response (Success - 200 OK):**
```json
{
    "districts": ["Kampala", "Wakiso", "Mukono", "Jinja"],
    "cities": ["Kampala", "Entebbe", "Nansana", "Kira"]
}
```

---

#### GET /public/properties - List Properties (Public Alternative)

**Description:** Same as `/properties` but explicitly under the public prefix.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/public/properties
```

---

#### GET /public/properties/{id} - Get Single Property (Public Alternative)

**Description:** Same as `/properties/{id}` but explicitly under the public prefix.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/public/properties/1
```

---

### Authenticated Endpoints (Require Bearer Token)

These endpoints require a valid access token in the `Authorization: Bearer <token>` header.

#### POST /properties - Create Property

**Description:** Create a new property listing.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/properties
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "title": "Beautiful House in Kampala",
    "description": "Spacious 3 bedroom house with garden",
    "price_ugx": 500000000,
    "price_currency": "UGX",
    "listing_type": "sale",
    "property_type": "House",
    "bedrooms": 3,
    "bathrooms": 2,
    "district": "Kampala",
    "city": "Kampala",
    "address": "Plot 123, Spring Hills",
    "latitude": 0.3166,
    "longitude": 32.5833,
    "status": "draft",
    "is_visible": true,
    "images": [
        {"path": "/storage/images/image1.jpg", "sort_order": 0}
    ]
}
```

**Required Fields:**
- `title` (string, max: 255)
- `description` (string)
- `price_ugx` (integer, min: 0)
- `listing_type` (string: `rent` or `sale`)
- `property_type` (string, max: 120)
- `district` (string, max: 120)
- `city` (string, max: 120)
- `address` (string, max: 255)

**Optional Fields:**
- `price_currency` (string: `UGX` or `USD`, default: `UGX`)
- `bedrooms` (integer, min: 0)
- `bathrooms` (integer, min: 0)
- `latitude` (numeric, between: -90, 90)
- `longitude` (numeric, between: -180, 180)
- `status` (string: `draft`, `published`, `archived`)
- `published_at` (date)
- `is_visible` (boolean)
- `images` (array of objects with `path` and optional `sort_order`)

**Response (Success - 201 Created):**
```json
{
    "id": 1,
    "title": "Beautiful House in Kampala",
    "description": "Spacious 3 bedroom house with garden",
    "price_ugx": 500000000,
    "price_currency": "UGX",
    "listing_type": "sale",
    "property_type": "House",
    "bedrooms": 3,
    "bathrooms": 2,
    "district": "Kampala",
    "city": "Kampala",
    "address": "Plot 123, Spring Hills",
    "latitude": 0.3166,
    "longitude": 32.5833,
    "status": "draft",
    "is_visible": true,
    "user_id": 1,
    "published_at": null,
    "created_at": "2026-08-11T10:00:00.000000Z",
    "updated_at": "2026-08-11T10:00:00.000000Z"
}
```

**Note:** In **DEMO_MODE** (when `DEMO_MODE=true` in server configuration), any authenticated user can create properties. In production mode, only users with `seller` or `admin` roles can create properties.

---

#### PUT /properties/{property} - Update Property

**Description:** Update an existing property. Only the property owner can update their own properties.

**Endpoint:**
```
PUT https://mycanopy.verbeek.ug/api/properties/1
PATCH https://mycanopy.verbeek.ug/api/properties/1
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "title": "Updated House Title",
    "price_ugx": 550000000,
    "status": "published"
}
```

**Response (Success - 200 OK):**
```json
{
    "id": 1,
    "title": "Updated House Title",
    "description": "Spacious 3 bedroom house with garden",
    "price_ugx": 550000000,
    ...
}
```

---

#### DELETE /properties/{property} - Delete Property

**Description:** Delete a property. Only the property owner can delete their own properties.

**Endpoint:**
```
DELETE https://mycanopy.verbeek.ug/api/properties/1
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Response (Success - 204 No Content):**
```
(Empty response body)
```

---

#### POST /uploads/images - Upload Image

**Description:** Upload an image file for a property.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/uploads/images
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
image: [file upload]
property_id: 1
```

**Response (Success - 201 Created):**
```json
{
    "id": 1,
    "path": "/storage/images/filename.jpg",
    "property_id": 1,
    "sort_order": 0,
    "created_at": "2026-08-11T10:00:00.000000Z"
}
```

---

#### GET /messages - List Messages

**Description:** Get the authenticated user's messages.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/messages
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

---

#### POST /messages - Send Message

**Description:** Send a message to another user.

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/messages
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
Accept: application/json
```

---

## User Roles and Permissions

| Role | Description | Can Create Properties | Can View All Properties | Can Manage Own Properties |
|------|-------------|----------------------|------------------------|--------------------------|
| `buyer` | Property buyer | No (unless DEMO_MODE) | Yes | Only own (if any) |
| `seller` | Property seller | Yes | Yes | Yes |
| `agent` | Real estate agent | Yes | Yes | Yes |
| `admin` | Administrator | Yes | Yes | All properties |

---

## Rate Limiting

Some endpoints have rate limiting:
- `POST /public/property-contact` - 20 requests per minute per IP

Standard Laravel rate limiting may apply to other endpoints (typically 60 requests per minute).

---

## Error Responses

| Status Code | Description | Response Format |
|-------------|-------------|-----------------|
| 200 | Success | `{ data: [...] }` or `{ ... }` |
| 201 | Created | `{ ... }` (new resource) |
| 204 | No Content | Empty body |
| 400 | Bad Request | `{ "message": "...", "errors": { ... } }` |
| 401 | Unauthorized | `{ "message": "Unauthenticated." }` |
| 403 | Forbidden | `{ "message": "..." }` |
| 404 | Not Found | `{ "message": "..." }` |
| 405 | Method Not Allowed | `{ "message": "..." }` |
| 422 | Unprocessable Entity | `{ "message": "...", "errors": { ... } }` |
| 429 | Too Many Requests | `{ "message": "Too Many Attempts." }` |
| 500 | Server Error | `{ "message": "..." }` |

---

## Demo Mode

When `DEMO_MODE=true` on the server:
- Any registered user can create properties without a seller subscription
- Properties are published immediately without payment
- Useful for testing and development

---

## Implementation Examples

### JavaScript (Fetch API)

```javascript
// Login
const login = async (email, password) => {
    const response = await fetch('https://mycanopy.verbeek.ug/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            email,
            password,
            device_name: 'my-app'
        })
    });
    
    const data = await response.json();
    if (response.ok) {
        localStorage.setItem('token', data.token);
        return data;
    }
    throw new Error(data.message);
};

// Get properties
const getProperties = async () => {
    const token = localStorage.getItem('token');
    const response = await fetch('https://mycanopy.verbeek.ug/api/properties?district=Kampala', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });
    return await response.json();
};

// Create property
const createProperty = async (propertyData) => {
    const token = localStorage.getItem('token');
    const response = await fetch('https://mycanopy.verbeek.ug/api/properties', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(propertyData)
    });
    return await response.json();
};
```

### Axios (JavaScript)

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://mycanopy.verbeek.ug/api/',
    headers: {
        'Accept': 'application/json'
    }
});

// Add token to requests
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Login
export const login = (email, password, deviceName = 'my-app') => {
    return api.post('auth/login', { email, password, device_name: deviceName });
};

// Get properties
export const getProperties = (params) => {
    return api.get('properties', { params });
};

// Create property
export const createProperty = (data) => {
    return api.post('properties', data);
};

// Get user info
export const getMe = () => {
    return api.get('auth/me');
};

// Logout
export const logout = () => {
    return api.post('auth/logout');
};
```

### PHP (cURL)

```php
<?php

$baseUrl = 'https://mycanopy.verbeek.ug/api/';
$token = null;

// Login
function login($email, $password, $deviceName = 'php-client') {
    global $baseUrl;
    
    $ch = curl_init($baseUrl . 'auth/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $password,
        'device_name' => $deviceName
    ]));
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data;
}

// Get properties
function getProperties($token, $params = []) {
    global $baseUrl;
    
    $query = http_build_query($params);
    $url = $baseUrl . 'properties' . ($query ? '?' . $query : '');
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data;
}

// Create property
function createProperty($token, $propertyData) {
    global $baseUrl;
    
    $ch = curl_init($baseUrl . 'properties');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($propertyData));
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data;
}

// Usage
$loginData = login('user@example.com', 'password');
$token = $loginData['token'];

$properties = getProperties($token, ['district' => 'Kampala']);

$newProperty = createProperty($token, [
    'title' => 'New Property',
    'description' => 'A great property',
    'price_ugx' => 100000000,
    'listing_type' => 'sale',
    'property_type' => 'House',
    'district' => 'Kampala',
    'city' => 'Kampala',
    'address' => '123 Street'
]);
```

### Python (Requests)

```python
import requests

BASE_URL = 'https://mycanopy.verbeek.ug/api/'

# Login
def login(email, password, device_name='python-client'):
    response = requests.post(
        BASE_URL + 'auth/login',
        json={
            'email': email,
            'password': password,
            'device_name': device_name
        },
        headers={
            'Accept': 'application/json'
        }
    )
    return response.json()

# Get properties with token
def get_properties(token, params=None):
    headers = {
        'Authorization': f'Bearer {token}',
        'Accept': 'application/json'
    }
    response = requests.get(BASE_URL + 'properties', headers=headers, params=params)
    return response.json()

# Create property
def create_property(token, data):
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
    response = requests.post(BASE_URL + 'properties', json=data, headers=headers)
    return response.json()

# Usage
login_data = login('user@example.com', 'password')
token = login_data['token']

properties = get_properties(token, {'district': 'Kampala'})
print(properties)

new_property = create_property(token, {
    'title': 'New Property',
    'description': 'A great property',
    'price_ugx': 100000000,
    'listing_type': 'sale',
    'property_type': 'House',
    'district': 'Kampala',
    'city': 'Kampala',
    'address': '123 Street'
})
print(new_property)
```

### Dart/Flutter

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class MyCanopyApi {
  static const String _baseUrl = 'https://mycanopy.verbeek.ug/api/';
  static String? _token;

  static Future<Map<String, dynamic>> login(String email, String password, {String deviceName = 'flutter-app'}) async {
    final response = await http.post(
      Uri.parse('${_baseUrl}auth/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': deviceName,
      }),
    );
    
    final data = jsonDecode(response.body);
    if (response.statusCode == 200) {
      _token = data['token'];
      return data;
    }
    throw Exception(data['message']);
  }

  static Future<Map<String, dynamic>> getProperties({Map<String, dynamic>? queryParams}) async {
    final url = Uri.parse('${_baseUrl}properties').replace(queryParameters: queryParams);
    final response = await http.get(
      url,
      headers: {
        'Authorization': 'Bearer $_token',
        'Accept': 'application/json',
      },
    );
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> createProperty(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('${_baseUrl}properties'),
      headers: {
        'Authorization': 'Bearer $_token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }

  static Future<void> logout() async {
    await http.post(
      Uri.parse('${_baseUrl}auth/logout'),
      headers: {
        'Authorization': 'Bearer $_token',
        'Accept': 'application/json',
      },
    );
    _token = null;
  }
}
```

---

## Best Practices

1. **Token Storage:**
   - Store tokens securely (use platform-specific secure storage)
   - Never hardcode tokens in source code
   - Never log tokens

2. **Token Expiration:**
   - Tokens currently don't expire by default (unless configured)
   - Implement token refresh logic if needed
   - Consider revoking and recreating tokens periodically

3. **Error Handling:**
   - Handle 401 Unauthorized by redirecting to login
   - Handle 422 Validation errors by displaying field-specific errors
   - Handle 429 Rate limit errors by implementing retry logic

4. **Security:**
   - Always use HTTPS (required in production)
   - Validate all API responses
   - Sanitize user input before sending to API

5. **Performance:**
   - Implement caching for frequently accessed data
   - Use pagination for lists
   - Consider implementing client-side caching with ETags

---

## Testing with Postman

### Step 1: Get Token

1. Create a new POST request to: `https://mycanopy.verbeek.ug/api/auth/login`
2. Set **Headers:**
   - `Content-Type: application/json`
   - `Accept: application/json`
3. Set **Body** (raw JSON):
   ```json
   {
       "email": "your@email.com",
       "password": "your-password",
       "device_name": "postman"
   }
   ```
4. Send request and copy the `token` from the response

### Step 2: Use Token

1. Create a new request (e.g., GET to `https://mycanopy.verbeek.ug/api/properties`)
2. Set **Headers:**
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Accept: application/json`
3. Send request

### Step 3: Test Public Endpoints

Public endpoints like `GET /properties` and `GET /locations` work without authentication.

---

## Support

For API support or questions:
- Check the MyCanopy documentation
- Contact the development team
- Report issues via the platform's support channels

---

*Last Updated: August 11, 2026*
*API Version: 1.0*
