#ECORIDE

Drive Sustainable Journeys, Empower Communities, Transform Travel

last-commit repo-top-language repo-language-count
Built with the tools and technologies:

JSON Markdown npm TOML Composer
esbuild Docker GitHub%20Actions PHP

Table of Contents

Overview
Getting Started
Prerequisites
Installation
Usage
Testing
Overview

EcoRide is an open-source platform tailored for building sustainable ride-sharing applications, combining robust backend architecture with streamlined deployment workflows. It provides essential tools for database setup, asset compilation, and seamless deployment on Fly.io, ensuring your app is production-ready with minimal hassle.

Why EcoRide?

This project aims to facilitate the development of eco-friendly transportation solutions. The core features include:

🛠️ 🔧 Database Initialization: Ensures consistent environment setup across deployments.
🚀 🎨 Asset Build Process: Automates frontend asset compilation for optimized delivery.
🌐 🛠️ Deployment Configuration: Simplifies hosting with flexible Fly.io setup.
📦 🧩 Modular Data Models: Supports scalable management of users, trips, bookings, and payments.
⚙️ 🤖 CI/CD Automation: Enables reliable, continuous deployment workflows.
Getting Started

Prerequisites

This project requires the following dependencies:

Programming Language: PHP
Package Manager: Npm, Composer
Container Runtime: Docker
Installation

Build EcoRide from the source and install dependencies:

Clone the repository:

❯ git clone https://github.com/OlivierGd/EcoRide
Navigate to the project directory:

❯ cd EcoRide
Install the dependencies:

Using docker:

❯ docker build -t OlivierGd/EcoRide .
Using npm:

❯ npm install
Using composer:

❯ composer install
Usage

Run the project with:

Using docker:

docker run -it {image_name}
Using npm:

npm start
Using composer:

php {entrypoint}
Testing

Ecoride uses the {test_framework} test framework. Run the test suite with:

Using docker:

echo 'INSERT-TEST-COMMAND-HERE'
Using npm:

npm test
Using composer:

vendor/bin/phpunit
⬆ Return
