CREATE TABLE users ( 
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    username TEXT, 
    nextcloud_username TEXT, 
    nc_access_token TEXT, 
    nc_refresh_token TEXT, 
    nc_token_expires_at INTEGER, 
    created_at DATETIME DEFAULT (datetime('now')) 
);

CREATE TABLE faillinks ( 
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    user_id INTEGER, 
    nextcloud_path TEXT NOT NULL, 
    nextcloud_fileid TEXT, 
    name TEXT NOT NULL, 
    mime TEXT, 
    size INTEGER, 
    raw_metadata TEXT, 
    created_at DATETIME DEFAULT (datetime('now')) 
);