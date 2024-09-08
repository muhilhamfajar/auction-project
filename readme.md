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
  - [API Documentation](#api-documentation)
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

6. Destroy the setup:
   ```
   make down
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

| Username           | Password       |
|--------------------|----------------|
| admin1@gmail.com   | Password!234   |
| admin2@gmail.com   | Password!234   |

### Regular User Accounts

| Username           | Password        |
|--------------------|-----------------|
| user1@gmail.com    | Password!234    |
| user2@gmail.com    | Password!234    |

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
   - Real-time updates of the highest bid
   - Table with bidding history for each item

4. **Auto-bidding**
   - Users can set maximum bid amount
   - Automatic outbidding by $1
   - Configurable bid alert notification
   - Processed asynchronously using Symfony Messenger

5. **Administrator Dashboard**
   - CRUD operations for auction items
   - View bids on items
   - Filter and manage items

6. **User Profile Page**
   - View list of items the user has bid on
   - See the current state of each bid (Lost, In progress, Won)
   - View historical bids and awarded items
   - Access bills for awarded items

7. **Auction Completion**
   - Automatic awarding of items to highest bidders when auction ends
   - Generation of bills for winning bids

8. **Email Notifications**
   - New bids on items of interest
   - Auction completion and item awarding
   - Alerts when total bid amount reaches a certain state
   - Notifications when current bid exceeds maximum auto-bid amount

9. **Responsive Design**
   - Mobile-friendly user interface

10. **RESTful API**
    - Backend built with Symfony, providing RESTful endpoints

11. **Frontend SPA**
    - Vue.js 3 with TypeScript for a dynamic user experience

12. **Database**
    - MySQL for data persistence
    - Migrations for version control of database schema

13. **Docker Deployment**
    - Containerized application for easy deployment and scaling

This application provides a comprehensive platform for antique item auctions, catering to both regular users and administrators with a range of features to enhance the bidding experience.

## API Documentation

The API for this application is documented using Swagger. We provide a `swagger.json` file that can be imported into Postman for easy API testing and exploration.

To use the API documentation:

1. Locate the `swagger.json` file in the project root directory.

2. Open Postman and follow these steps to import the Swagger documentation:
   - Click on "Import" in the top left corner
   - Select "File" and choose the `swagger.json` file
   - Click "Import" to add the API documentation to your Postman workspace

3. Once imported, you'll have access to all the API endpoints, complete with descriptions, request parameters, and response schemas.

4. You can now use Postman to test the API endpoints, examine request/response structures, and integrate with your development workflow.

This Swagger documentation provides a clear picture of the API structure, making it easier for developers to understand and consume the endpoints.

## Nice to Have

The following features are not currently implemented but would be valuable additions to the project:

* [ ] Permissions feature in backend and frontend
* [ ] Real-time notifications
* [ ] Real-time update item detail
* [ ] Enhance component library with versatile, reusable elements to streamline development and ensure UI consistency

These features could significantly enhance the user experience and functionality of the application. Contributions in these areas are welcome!