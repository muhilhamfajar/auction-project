# Antique Items Auction Web Application

This project is a web auction application for antique items. It allows users to bid on antique items and administrators to set up items for auction.

## Table of Contents
- [Antique Items Auction Web Application](#antique-items-auction-web-application)
  - [Table of Contents](#table-of-contents)
  - [Setup](#setup)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
  - [Running Locally](#running-locally)
    - [Additional Commands](#additional-commands)
  - [Test Accounts](#test-accounts)
    - [Admin Accounts](#admin-accounts)
    - [Regular User Accounts](#regular-user-accounts)
  - [Main Features](#main-features)
  - [Nice to Have](#nice-to-have)

## Setup

### Prerequisites
- Docker and Docker Compose
- Make (optional, but recommended)

### Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd <project-directory>
   ```

2. Build the Docker containers:
   ```
   make build
   ```

3. Start the application:
   ```
   make up
   ```

4. Install dependencies:
   ```
   make install
   ```

5. Set up the database and load fixtures:
   ```
   make migrate
   ```

## Running Locally

1. Ensure the application is running:
   ```
   make up
   ```

2. Start the Symfony Messenger consumer:
   ```
   make messenger-start
   ```

3. Access the application:
   - Frontend: `http://localhost:5173`
   - Backend API: `http://localhost/api`

4. To stop the application:
   ```
   make down
   ```

### Additional Commands

- View logs:
  ```
  make logs
  ```

- Run tests:
  ```
  make test
  ```

- Access PHP container shell:
  ```
  make shell-php
  ```

- Access Frontend container shell:
  ```
  make shell-frontend
  ```

- Stop Symfony Messenger consumer:
  ```
  make messenger-stop
  ```

- Check Symfony Messenger status:
  ```
  make messenger-status
  ```

## Test Accounts

The application comes with pre-configured test accounts for both admin and regular user roles. You can use these accounts to log in and test different features of the application.

### Admin Accounts

| Username | Password |
|----------|----------|
| admin1   | admin1   |
| admin2   | admin2   |

### Regular User Accounts

| Username | Password |
|----------|----------|
| user1    | user1    |
| user2    | user2    |

Please note that these are test accounts with predefined credentials. In a production environment, you should always use strong, unique passwords and follow security best practices.

## Main Features

1. **User Authentication**
   - Login system with role-based access (Admin and Regular users)
   - Protected routes for authenticated users

2. **Home Page**
   - Display list of auction items
   - Pagination (10 items per page)
   - Search functionality (filter by Name and Description)
   - Sortable Price column

3. **Item Details Page**
   - Countdown timer for auction end time
   - Bidding functionality
   - Auto-bidding feature

4. **Auto-bidding**
   - Users can set maximum bid amount
   - Automatic outbidding by $1
   - Configurable bid alert notification
   - Processed asynchronously using Symfony Messenger

5. **Administrator Dashboard**
   - CRUD operations for auction items
   - View bids on items
   - Filter and manage items

6. **Responsive Design**
   - Mobile-friendly user interface

7. **RESTful API**
   - Backend built with Symfony, providing RESTful endpoints

8. **Frontend SPA**
   - Vue.js 3 with TypeScript for a dynamic user experience

9. **Database**
    - MySQL for data persistence
    - Migrations for version control of database schema

This application provides a comprehensive platform for antique item auctions, catering to both regular users and administrators with a range of features to enhance the bidding experience.

## Nice to Have

The following features are not currently implemented but would be valuable additions to the project:

* [ ] Permissions feature in backend and frontend
* [ ] Real-time notifications
* [ ] Enhance component library with versatile, reusable elements to streamline development and ensure UI consistency
* [ ] Automatically close auctions

These features could significantly enhance the user experience and functionality of the application. Contributions in these areas are welcome!