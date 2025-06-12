#!/bin/bash
# Start MySQL in the background
service mysql start

# Wait until MySQL is ready
until mysqladmin ping -h localhost --silent; do
    echo "Waiting for MySQL to be ready... ROFL"
    sleep 2
done

# Create the database if it doesn't exist
echo "CREATE DATABASE IF NOT EXISTS ci4login;" | mysql -u root

# Import database dump
mysql -u root ci4login < /var/www/html/ci4login.sql

# Keep Apache running
exec apachectl -D FOREGROUND