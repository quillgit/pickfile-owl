# Pickfile OWL - Nextcloud File Picker

A lightweight PHP application that provides seamless Nextcloud file integration for the **ERP OWL-Plantation System**. This tool enables users to browse, select, and integrate files from their Nextcloud storage directly into the plantation management workflow.

## 🌟 Features

- **Nextcloud OAuth Integration** - Secure authentication with Nextcloud instances
- **File Browser Interface** - Vue.js-powered file picker with breadcrumb navigation
- **WebDAV Support** - Direct file access and manipulation through WebDAV protocol
- **Faillink System** - Secure file sharing with controlled access
- **ERP Integration Ready** - Designed specifically for OWL-Plantation System integration
- **SQLite Database** - Lightweight data storage for tokens and file metadata
- **Responsive UI** - Modern web interface that works across devices

## 🏗️ Project Structure

```
pickfile-owl/
├── config.php.example          # Configuration template
├── src/                        # PHP Classes
│   ├── NCClient.php            # Nextcloud OAuth & WebDAV client
│   ├── TokenStore.php          # Token management
│   ├── FaillinkModel.php       # File link operations
│   └── UserModel.php           # User management
├── public/                     # Web accessible files
│   ├── index.php              # Main application controller
│   └── picker.html            # Vue.js file picker interface
├── migrations/                 # Database schema
│   └── create_tables.sql      # Table creation scripts
└── data/                      # SQLite database storage
```

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- SQLite3 extension
- cURL extension
- Web server (Apache/Nginx)
- Nextcloud instance with OAuth2 app enabled

### Setup Steps

1. **Clone the repository**

```sh
   git clone <repository-url> pickfile-owl
   cd pickfile-owl
```

2. **Configure the application**

```sh
   cp config.php.example config.php
```

Edit `config.php` with your settings:

```php
   return [
       'db_path' => __DIR__ . '/data/pickfile.db',
       'nextcloud_url' => 'https://your-nextcloud.com',
       'oauth_client_id' => 'your-oauth-client-id',
       'oauth_client_secret' => 'your-oauth-client-secret',
       'oauth_redirect_uri' => 'https://your-domain.com/nextcloud/callback',
       'app_secret' => 'your-random-secret-key'
   ];
```

3. **Set up the database**

```sh
   sqlite3 data/pickfile.db < migrations/create_tables.sql
```

4. **Configure web server**
   Point your web server document root to the `public/` directory.

## 🔧 ERP OWL-Plantation System Integration

This application is specifically designed to integrate with the **OWL-Plantation System** ERP. 

### Integration Tags

- `#erp-integration`
- `#owl-plantation`
- `#nextcloud-connector`
- `#file-management`
- `#plantation-erp`

### Integration Points

1. **File Picker Embedding**

```html
   <iframe src="https://your-domain.com/picker.html" 
           width="100%" height="600px"></iframe>
```

2. **API Endpoints for ERP**
   - `GET /nextcloud/list` - Browse Nextcloud files
   - `POST /nextcloud/pick` - Select files for ERP use
   - `GET /faillink/download/{id}` - Secure file download
   - `POST /nextcloud/connect` - OAuth authentication

3. **Event Handling**

```js
   window.addEventListener('faillink-picked', function(event) {
       // Handle picked file in ERP system
       const fileData = event.detail;
       // Integrate with OWL-Plantation workflows
   });
```

## 🔐 Security Features

- **OAuth2 Authentication** - Secure Nextcloud integration
- **Token Management** - Encrypted token storage
- **Faillink System** - Controlled file access with expiration
- **CSRF Protection** - Built-in request validation
- **Secure File Proxying** - No direct file exposure

## 📡 API Reference

### Authentication
- `GET /nextcloud/connect` - Initiate OAuth flow
- `GET /nextcloud/callback` - OAuth callback handler

### File Operations
- `GET /nextcloud/list?path={path}` - List directory contents
- `POST /nextcloud/pick` - Create faillink for selected file
- `GET /faillink/download/{id}` - Download file via faillink

### Response Format

```json
{
  "id": "faillink-id",
  "name": "filename.pdf",
  "size": 1024,
  "mime": "application/pdf",
  "created_at": "2024-01-01T00:00:00Z"
}
```

```sh
# Start PHP development server
php -S localhost:8000 -t public/

# Access the application
open http://localhost:8000
```

### Database Migrations

```sh
# Run migrations
sqlite3 data/pickfile.db < migrations/create_tables.sql
```

## 📝 Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `db_path` | SQLite database file path | `./data/pickfile.db` |
| `nextcloud_url` | Nextcloud instance URL | Required |
| `oauth_client_id` | OAuth2 client ID | Required |
| `oauth_client_secret` | OAuth2 client secret | Required |
| `oauth_redirect_uri` | OAuth2 redirect URI | Required |
| `app_secret` | Application secret key | Required |

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test with OWL-Plantation System integration
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.