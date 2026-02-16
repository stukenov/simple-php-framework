# Simple PHP Webman Framework

Lightweight PHP framework with HRM system and Craigslist-style blog example applications.

## Design Philosophy

The interface is built in **classic Craigslist style**:
- White background, black text
- Minimalist forms and tables
- Simple HTML elements without decorations
- Blue links, gray borders

## Quick Start

1. **Database is pre-configured** (SQLite with test data)

2. **Start the server:**
   ```bash
   php windows.php start
   ```

   On Linux/macOS:
   ```bash
   php start.php start
   ```

3. **Open in browser:**
   - **HRM Dashboard:** `http://localhost:8787/hrm`
   - **Blog:** `http://localhost:8787/blog`

## Features

### HRM System (Kazakhstan-compliant)
- Employee management (IIN, personal data)
- Employment contracts (permanent, fixed-term, civil law)
- Salary calculation (IPN 10%, OPV 10%, OMS 2%)
- Leave management (24 days minimum)
- Time tracking (40 h/week max)
- Tax reporting

### Blog/Classified Ads
- Create and view posts
- Categories and metadata
- Simple text interface

## Tech Stack

- **Webman Framework** - High-performance PHP workerman-based framework
- **SQLite** - Embedded database
- **PSR-4** autoloading
- **Minimalist design** inspired by Craigslist

## Installation

1. Clone the repository:
```bash
git clone https://github.com/stukenov/simple-php-framework.git
cd simple-php-framework
```

2. Install dependencies:
```bash
composer install
```

3. Run the application:
```bash
php windows.php start  # Windows
# or
php start.php start    # Linux/macOS
```

## Project Structure

```
simple-php-framework/
├── app/              # Application code
│   ├── controller/   # Controllers
│   ├── model/        # Models
│   └── view/         # Views
├── config/           # Configuration files
├── public/           # Public assets
├── runtime/          # Logs and cache
└── support/          # Helper classes
```

## Requirements

- PHP 7.4 or higher
- Composer
- SQLite extension

## License

MIT License - see LICENSE file for details

## Author

Saken Tukenov

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
