# MyCanopy API Guide for External Applications

## Overview

This guide explains how **external applications** (mobile apps, web apps, backend services, or third-party integrations) can authenticate and consume the MyCanopy API at `https://mycanopy.verbeek.ug/api/` using **OAuth2 with client_id and client_secret**.

**Important:** This API uses OAuth2 **client credentials grant** only. User login/password authentication is not supported for external applications.

---

## Base URL

```
https://mycanopy.verbeek.ug/api/
```

---

## Authentication

MyCanopy provides **OAuth2 client credentials authentication** for external applications. Your app authenticates using its own `client_id` and `client_secret` to obtain an access token.

### OAuth2 Implementation

| Feature | Status |
|---------|--------|
| **Grant Type** | `client_credentials` only |
| **Token Format** | Sanctum Bearer Token |
| **Refresh Tokens** | Not supported |
| **Token Scopes** | Supported |
| **Setup Complexity** | Simple (pre-configured) |

---

## Quick Start

### 1. Get Client Credentials

Run the seeder to create a default API client:
```bash
php artisan db:seed --class=ApiClientsSeeder
```

This creates:
- **Client ID:** `mycanopy-mobile-app`
- **Client Secret:** `mobile-app-secret-change-me`

**⚠️ IMPORTANT:** Change the client secret in production!

### 2. Get Access Token

```bash
curl -X POST https://mycanopy.verbeek.ug/api/oauth/token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "mycanopy-mobile-app",
    "client_secret": "mobile-app-secret-change-me",
    "scope": "*"
  }'
```

### 3. Use the Token

```bash
curl -X GET https://mycanopy.verbeek.ug/api/properties \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

---

## OAuth2 Client Credentials Flow

### Token Endpoint

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/oauth/token
```

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body (JSON):**
```json
{
    "grant_type": "client_credentials",
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "scope": "*"
}
```

**Request Body (x-www-form-urlencoded):**
```
grant_type=client_credentials&client_id=your-client-id&client_secret=your-client-secret&scope=*
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `grant_type` | string | Yes | Must be `client_credentials` |
| `client_id` | string | Yes | Your app's client ID |
| `client_secret` | string | Yes | Your app's client secret |
| `scope` | string | No | Space-separated scopes. Default: `*` |

**Response (Success - 200 OK):**
```json
{
    "token_type": "Bearer",
    "expires_in": null,
    "access_token": "1|abc123def456...",
    "refresh_token": null,
    "client_id": "your-client-id",
    "user_id": 1,
    "scopes": ["*"]
}
```

**Response (Error):**
```json
{
    "error": "invalid_client",
    "error_description": "The client credentials are invalid."
}
```

**Error Codes:**
| Error | HTTP | Description |
|-------|------|-------------|
| `invalid_request` | 400 | Missing required parameters |
| `invalid_client` | 401 | Invalid client_id or client_secret |
| `invalid_scope` | 400 | Requested scope is not allowed |
| `unsupported_grant_type` | 400 | grant_type must be `client_credentials` |

---

## API Client Management (Admin Only)

### Create API Client

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/oauth/clients
```

**Headers:**
```
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "name": "My Mobile App",
    "redirect_uri": "https://myapp.com/callback",
    "scopes": ["properties.read", "properties.write"]
}
```

**Response (201 Created):**
```json
{
    "message": "API client created successfully.",
    "client": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "My Mobile App",
        "client_id": "abc123",
        "client_secret": "xyz789",
        "redirect_uri": "https://myapp.com/callback",
        "scopes": ["properties.read", "properties.write"],
        "created_at": "2026-08-12T10:00:00.000000Z"
    },
    "warning": "Store the client_secret securely. It will not be shown again."
}
```

### List API Clients

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/oauth/clients
```

**Headers:**
```
Authorization: Bearer ADMIN_TOKEN
Accept: application/json
```

**Response:**
```json
{
    "clients": [
        {
            "id": 1,
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "name": "My Mobile App",
            "client_id": "abc123",
            "redirect_uri": "https://myapp.com/callback",
            "revoked": false,
            "created_at": "2026-08-12T10:00:00.000000Z"
        }
    ]
}
```

### Get Client

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/oauth/clients/{client}
```

### Delete Client

**Endpoint:**
```
DELETE https://mycanopy.verbeek.ug/api/oauth/clients/{client}
```

### Regenerate Client Secret

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/oauth/clients/{client}/regenerate
```

---

## Token Management

### Revoke Token

**Endpoint:**
```
POST https://mycanopy.verbeek.ug/api/oauth/revoke
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "token": "1|abc123def456..."
}
```

### Get Token Info

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/oauth/token/info?token=YOUR_TOKEN
```

---

## Scopes

| Scope | Description |
|-------|-------------|
| `properties.read` | View properties |
| `properties.write` | Create, update, delete properties |
| `messages.read` | View messages |
| `messages.write` | Send messages |
| `*` | All permissions |

---

## API Endpoints

### Public Endpoints (No Authentication Required)

These endpoints are accessible without an access token. They return only published, visible content.

---

#### GET /properties - List Properties

**Description:** Retrieve a paginated list of published properties with optional filtering.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/properties
```

**Headers:**
```
Accept: application/json
```

**Query Parameters:**
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `district` | string | Filter by district | `Kampala` |
| `city` | string | Filter by city | `Kampala` |
| `location` | string | Filter by location | `Kampala Central` |
| `listing_type` | string | Filter by type: `rent` or `sale` | `sale` |
| `property_type` | string | Filter by property type | `House` |
| `bedrooms` | integer | Filter by number of bedrooms | `3` |
| `bathrooms` | integer | Filter by number of bathrooms | `2` |
| `min_price` | integer | Minimum price in UGX | `100000000` |
| `max_price` | integer | Maximum price in UGX | `500000000` |
| `per_page` | integer | Items per page (1-100) | `10` |
| `page` | integer | Page number | `1` |

**Example Request:**
```bash
curl -X GET "https://mycanopy.verbeek.ug/api/properties?district=Kampala&listing_type=sale&bedrooms=3&per_page=10" \
  -H "Accept: application/json"
```

**Response (200 OK):**
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
            "user": {"id": 1, "name": "John Doe"},
            "images": [{"id": 1, "path": "/storage/images/image1.jpg", "sort_order": 0}],
            "created_at": "2026-08-12T10:00:00.000000Z",
            "updated_at": "2026-08-12T10:00:00.000000Z"
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
        "path": "https://mycanopy.verbeek.ug/api/properties",
        "per_page": 10,
        "to": 10,
        "total": 50
    }
}
```

---

#### GET /properties/{id} - Get Single Property

**Description:** Retrieve details for a specific property.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/properties/1
```

**Headers:**
```
Accept: application/json
```

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Property ID |

**Example Request:**
```bash
curl -X GET "https://mycanopy.verbeek.ug/api/properties/1" \
  -H "Accept: application/json"
```

**Response (200 OK):**
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
    "published_at": "2026-08-12T10:00:00.000000Z",
    "user": {"id": 1, "name": "John Doe"},
    "images": [{"id": 1, "path": "/storage/images/image1.jpg", "sort_order": 0}],
    "created_at": "2026-08-12T10:00:00.000000Z",
    "updated_at": "2026-08-12T10:00:00.000000Z"
}
```

---

#### GET /locations - Get Available Locations

**Description:** Retrieve a list of all available districts and cities.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/locations
```

**Headers:**
```
Accept: application/json
```

**Example Request:**
```bash
curl -X GET "https://mycanopy.verbeek.ug/api/locations" \
  -H "Accept: application/json"
```

**Response (200 OK):**
```json
{
    "districts": ["Kampala", "Wakiso", "Mukono", "Jinja", "Mbarara", "Gulu"],
    "cities": ["Kampala", "Entebbe", "Nansana", "Kira", "Makindye", "Nakawa"]
}
```

---

#### GET /public/properties - List Properties (Public Alternative)

**Description:** Same as `/properties` but explicitly under the public prefix.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/public/properties
```

**Supports the same query parameters as `/properties`**

---

#### GET /public/properties/{id} - Get Single Property (Public Alternative)

**Description:** Same as `/properties/{id}` but explicitly under the public prefix.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/public/properties/1
```

---

### Authenticated Endpoints (Require Bearer Token)

These endpoints require a valid `Authorization: Bearer <token>` header.

---

#### POST /properties - Create Property

**Description:** Create a new property listing. Requires `properties.write` scope.

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
    "images": [{"path": "/storage/images/image1.jpg", "sort_order": 0}]
}
```

**Required Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `title` | string | Property title (max: 255) |
| `description` | string | Property description |
| `price_ugx` | integer | Price in UGX (min: 0) |
| `listing_type` | string | Either `rent` or `sale` |
| `property_type` | string | Type of property (max: 120) |
| `district` | string | District/region (max: 120) |
| `city` | string | City/town (max: 120) |
| `address` | string | Street address (max: 255) |

**Optional Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `price_currency` | string | `UGX` or `USD` (default: `UGX`) |
| `bedrooms` | integer | Number of bedrooms |
| `bathrooms` | integer | Number of bathrooms |
| `latitude` | number | GPS latitude (-90 to 90) |
| `longitude` | number | GPS longitude (-180 to 180) |
| `status` | string | `draft`, `published`, or `archived` (default: `draft`) |
| `is_visible` | boolean | Whether property is visible (default: `true`) |
| `published_at` | date | Date when property was published |
| `images` | array | Array of image objects with `path` and `sort_order` |

**Example Request:**
```bash
curl -X POST "https://mycanopy.verbeek.ug/api/properties" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Modern Apartment",
    "description": "2 bedroom apartment in the city center",
    "price_ugx": 150000000,
    "listing_type": "sale",
    "property_type": "Apartment",
    "district": "Kampala",
    "city": "Kampala",
    "address": "Plot 456, Central Division",
    "bedrooms": 2,
    "bathrooms": 2
  }'
```

**Response (201 Created):**
```json
{
    "id": 1,
    "title": "Modern Apartment",
    "description": "2 bedroom apartment in the city center",
    "price_ugx": 150000000,
    "price_currency": "UGX",
    "listing_type": "sale",
    "property_type": "Apartment",
    "bedrooms": 2,
    "bathrooms": 2,
    "district": "Kampala",
    "city": "Kampala",
    "address": "Plot 456, Central Division",
    "latitude": null,
    "longitude": null,
    "status": "draft",
    "is_visible": true,
    "user_id": 1,
    "published_at": null,
    "created_at": "2026-08-12T10:00:00.000000Z",
    "updated_at": "2026-08-12T10:00:00.000000Z"
}
```

---

#### PUT /properties/{property} - Update Property

**Description:** Update an existing property. Requires `properties.write` scope.

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

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `property` | integer | Property ID |

**Request Body:**
```json
{
    "title": "Updated Apartment Title",
    "price_ugx": 160000000,
    "status": "published"
}
```

**Example Request:**
```bash
curl -X PUT "https://mycanopy.verbeek.ug/api/properties/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Updated Title",
    "price_ugx": 160000000,
    "status": "published"
  }'
```

**Response (200 OK):**
```json
{
    "id": 1,
    "title": "Updated Apartment Title",
    "description": "2 bedroom apartment in the city center",
    "price_ugx": 160000000,
    "status": "published",
    "user_id": 1,
    "updated_at": "2026-08-12T10:30:00.000000Z"
}
```

---

#### DELETE /properties/{property} - Delete Property

**Description:** Delete a property. Requires `properties.write` scope.

**Endpoint:**
```
DELETE https://mycanopy.verbeek.ug/api/properties/1
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `property` | integer | Property ID |

**Example Request:**
```bash
curl -X DELETE "https://mycanopy.verbeek.ug/api/properties/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response (204 No Content):**
```
(Empty body)
```

---

#### POST /uploads/images - Upload Property Image

**Description:** Upload an image for a property. Requires `properties.write` scope.

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

**Form Data:**
| Field | Type | Required |
|-------|------|----------|
| `image` | file | Yes |
| `property_id` | integer | Yes |

**Example Request:**
```bash
curl -X POST "https://mycanopy.verbeek.ug/api/uploads/images" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -F "image=@/path/to/image.jpg" \
  -F "property_id=1"
```

**Response (201 Created):**
```json
{
    "id": 1,
    "path": "/storage/images/filename.jpg",
    "property_id": 1,
    "sort_order": 0,
    "created_at": "2026-08-12T10:00:00.000000Z"
}
```

---

#### GET /messages - List Messages

**Description:** Retrieve messages. Requires `messages.read` scope.

**Endpoint:**
```
GET https://mycanopy.verbeek.ug/api/messages
```

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Messages per page (default: 15) |
| `page` | integer | Page number |

**Example Request:**
```bash
curl -X GET "https://mycanopy.verbeek.ug/api/messages" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "subject": "Inquiry about property",
            "body": "Hello, I am interested...",
            "sender": {"id": 2, "name": "Jane Doe"},
            "receiver": {"id": 1, "name": "John Doe"},
            "read_at": null,
            "created_at": "2026-08-12T10:00:00.000000Z"
        }
    ],
    "links": {...},
    "meta": {...}
}
```

---

#### POST /messages - Send Message

**Description:** Send a message. Requires `messages.write` scope.

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

**Request Body:**
```json
{
    "receiver_id": 2,
    "subject": "Property Inquiry",
    "body": "Hello, I am interested in your property."
}
```

**Required Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `receiver_id` | integer | Recipient user ID |
| `body` | string | Message content |

**Optional Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `subject` | string | Message subject |

**Example Request:**
```bash
curl -X POST "https://mycanopy.verbeek.ug/api/messages" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "receiver_id": 2,
    "subject": "Inquiry",
    "body": "Hello, I am interested in your property."
  }'
```

**Response (201 Created):**
```json
{
    "id": 1,
    "subject": "Inquiry",
    "body": "Hello, I am interested in your property.",
    "sender_id": 1,
    "receiver_id": 2,
    "read_at": null,
    "created_at": "2026-08-12T10:00:00.000000Z"
}
```

---

## Code Examples

### JavaScript (Fetch)

```javascript
const API_BASE = 'https://mycanopy.verbeek.ug/api/';
const CLIENT_ID = 'mycanopy-mobile-app';
const CLIENT_SECRET = 'mobile-app-secret-change-me';

const getOAuthToken = async () => {
    const response = await fetch(API_BASE + 'oauth/token', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({
            grant_type: 'client_credentials',
            client_id: CLIENT_ID,
            client_secret: CLIENT_SECRET,
            scope: '*'
        })
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error_description);
    return data.access_token;
};

const getProperties = async (params = {}) => {
    const token = await getOAuthToken();
    const query = new URLSearchParams(params).toString();
    const response = await fetch(API_BASE + 'properties?' + query, {
        headers: {'Authorization': `Bearer ${token}`, 'Accept': 'application/json'}
    });
    return await response.json();
};
```

### Axios (JavaScript)

```javascript
import axios from 'axios';

const api = axios.create({ 
    baseURL: 'https://mycanopy.verbeek.ug/api/', 
    headers: {'Accept': 'application/json'} 
});

let cachedToken = null;

const getToken = async () => {
    if (cachedToken) return cachedToken;
    const response = await axios.post('/oauth/token', {
        grant_type: 'client_credentials',
        client_id: process.env.REACT_APP_CLIENT_ID,
        client_secret: process.env.REACT_APP_CLIENT_SECRET,
        scope: '*'
    }, { headers: {'Content-Type': 'application/json'} });
    cachedToken = response.data.access_token;
    return cachedToken;
};

api.interceptors.request.use(async (config) => {
    config.headers.Authorization = `Bearer ${await getToken()}`;
    return config;
});

export const getProperties = (params) => api.get('properties', { params });
export const createProperty = (data) => api.post('properties', data);
export const updateProperty = (id, data) => api.put(`properties/${id}`, data);
export const deleteProperty = (id) => api.delete(`properties/${id}`);
```

### PHP (cURL)

```php
<?php
$baseUrl = 'https://mycanopy.verbeek.ug/api/';
$clientId = 'mycanopy-mobile-app';
$clientSecret = 'mobile-app-secret-change-me';
$token = null;

function getOAuthToken() {
    global $baseUrl, $clientId, $clientSecret, $token;
    if ($token) return $token;
    
    $ch = curl_init($baseUrl . 'oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => '*'
        ])
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    if (isset($data['error'])) throw new Exception($data['error_description']);
    return $token = $data['access_token'];
}

function getProperties($params = []) {
    $token = getOAuthToken();
    $url = $baseUrl . 'properties' . (http_build_query($params) ? '?' . http_build_query($params) : '');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json']
    ]);
    return json_decode(curl_exec($ch), true);
}
```

### Python (Requests)

```python
import requests

BASE_URL = 'https://mycanopy.verbeek.ug/api/'
CLIENT_ID = 'mycanopy-mobile-app'
CLIENT_SECRET = 'mobile-app-secret-change-me'
token_cache = {'token': None}

def get_oauth_token():
    if token_cache['token']:
        return token_cache['token']
    response = requests.post(
        BASE_URL + 'oauth/token',
        json={
            'grant_type': 'client_credentials',
            'client_id': CLIENT_ID,
            'client_secret': CLIENT_SECRET,
            'scope': '*'
        },
        headers={'Accept': 'application/json'}
    )
    data = response.json()
    if response.status_code != 200:
        raise Exception(data.get('error_description', 'Unknown error'))
    token_cache['token'] = data['access_token']
    return token_cache['token']

def get_properties(params=None):
    token = get_oauth_token()
    response = requests.get(
        BASE_URL + 'properties', 
        headers={'Authorization': f'Bearer {token}', 'Accept': 'application/json'},
        params=params
    )
    return response.json()
```

### Dart/Flutter

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class MyCanopyApi {
  static const _baseUrl = 'https://mycanopy.verbeek.ug/api/';
  static const _clientId = 'mycanopy-mobile-app';
  static const _clientSecret = 'mobile-app-secret-change-me';
  static String? _token;

  static Future<String> _getToken() async {
    if (_token != null) return _token!;
    final response = await http.post(
      Uri.parse('${_baseUrl}oauth/token'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'grant_type': 'client_credentials',
        'client_id': _clientId,
        'client_secret': _clientSecret,
        'scope': '*'
      }),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode != 200) throw Exception(data['error_description']);
    return _token = data['access_token'];
  }

  static Future<Map<String, dynamic>> getProperties({Map<String, dynamic>? params}) async {
    final token = await _getToken();
    final url = Uri.parse('${_baseUrl}properties').replace(queryParameters: params);
    final response = await http.get(url, headers: {'Authorization': 'Bearer $token', 'Accept': 'application/json'});
    return jsonDecode(response.body);
  }
}
```

---

## Postman Testing

### 1. Get OAuth Token

**Method:** POST
**URL:** `https://mycanopy.verbeek.ug/api/oauth/token`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (raw JSON):**
```json
{
    "grant_type": "client_credentials",
    "client_id": "mycanopy-mobile-app",
    "client_secret": "mobile-app-secret-change-me",
    "scope": "*"
}
```

**Save the token as an environment variable:** `access_token`

### 2. Test Public Endpoints (No Token Required)

**GET /properties**
```
Method: GET
URL: https://mycanopy.verbeek.ug/api/properties?district=Kampala&per_page=5
Headers: Accept: application/json
```

**GET /locations**
```
Method: GET
URL: https://mycanopy.verbeek.ug/api/locations
Headers: Accept: application/json
```

### 3. Test Authenticated Endpoints

**GET /properties (with auth)**
```
Method: GET
URL: https://mycanopy.verbeek.ug/api/properties
Headers:
  Authorization: Bearer {{access_token}}
  Accept: application/json
```

**POST /properties**
```
Method: POST
URL: https://mycanopy.verbeek.ug/api/properties
Headers:
  Authorization: Bearer {{access_token}}
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
    "title": "Test Property",
    "description": "Testing from Postman",
    "price_ugx": 100000000,
    "listing_type": "sale",
    "property_type": "House",
    "district": "Kampala",
    "city": "Kampala",
    "address": "Test Address"
}
```

---

## Error Responses

| Status | Error | Description |
|--------|-------|-------------|
| 400 | `invalid_request` | Missing parameters |
| 400 | `invalid_scope` | Scope not allowed |
| 400 | `unsupported_grant_type` | grant_type must be `client_credentials` |
| 401 | `invalid_client` | Invalid client_id or client_secret |
| 401 | `invalid_token` | Token expired or invalid |
| 403 | `insufficient_scope` | Missing required scope |
| 404 | `not_found` | Resource not found |
| 422 | `validation_error` | Validation failed |
| 429 | `too_many_requests` | Rate limit exceeded |

---

## Best Practices

- **Cache tokens** to avoid new token requests for each API call
- **Store client_secret securely** — use environment variables, never commit to git
- **Use HTTPS** always in production
- **Request minimal scopes** — follow least privilege principle
- **Rotate secrets** periodically
- **Handle 401 errors** by obtaining a new token

---

## Setup

```bash
php artisan migrate
php artisan db:seed --class=ApiClientsSeeder
php artisan config:clear
```

---

## Support

For API support, contact the development team.

---

*Last Updated: August 12, 2026*
*API Version: 2.0*
