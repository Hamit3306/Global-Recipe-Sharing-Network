# Global Recipe Sharing Network

## Description
Global Recipe Sharing Network is a PHP-based web application that allows culinary enthusiasts from around the world to discover, share, and discuss recipes. Users can create profiles, upload their own recipes, connect with other chefs, and participate in food-related events.

## Features
* User Authentication: Secure registration, login, and logout system.
* User Profiles: Dedicated user profiles to showcase personal recipes and activities.
* Recipe Management: Add and share new recipes with the community.
* Detailed Recipe Pages: View comprehensive ingredients, instructions, and images for each recipe.
* Find Chefs: Discover and connect with other culinary enthusiasts on the platform.
* Culinary Events: View and engage with upcoming food-related events.
* Admin Panel: Dedicated dashboard for application management and moderation.
* Data Import: Functionality to bulk import recipe data via CSV files.

## Technologies Used
* PHP
* MySQL
* HTML / CSS
* JavaScript

## Setup Instructions

1. Clone or download the repository to your local machine.
2. Place the project folder in the root directory of your local web server (e.g., `htdocs` for XAMPP or `www` for WAMP/MAMP).
3. Ensure your local MySQL server is running.
4. Open `db.php` and update the database configuration settings to match your local MySQL credentials.
5. Navigate to the project directory in your browser and run `setup.php` (or execute `setup_full.bat` from the terminal) to automatically create the database schema and required tables.
6. (Optional) Run `import_csv.php` to populate the database with initial recipe data using the provided `recipes_data.csv` file.
7. Access `index.php` through your web browser to start using the application.

## Core File Structure
* `index.php`: Main landing page and global recipe feed.
* `detail.php`: Displays full details for a selected recipe.
* `profile.php`: User dashboard and personal recipe collection.
* `login.php` / `register.php`: User authentication interfaces.
* `add_recipe.php`: Form to submit new recipes.
* `find_chefs.php`: Page to search and discover other users.
* `events.php`: Listing of community events.
* `admin.php`: Administrative control panel.
* `db.php`: Database connection configuration.
* `setup.php` / `setup_full.bat`: Automated database initialization scripts.
