<!-- // migration.php -->
<?php
require_once 'connection.php';

// 1. Backup existing data
$backupQueries = [
    "CREATE TABLE projects_backup SELECT * FROM projects",
    "CREATE TABLE mainimages_backup SELECT * FROM mainimages",
    "CREATE TABLE carouselimages_backup SELECT * FROM carouselimages"
];

// 2. Migrate data to new schema
$migrationQueries = [
    // Migrate projects
    "INSERT INTO new_projects (project_id, project_name, project_description, 
                               project_location, project_date, project_type, created_at)
     SELECT project_id, project_name, project_description, 
            COALESCE(project_location, ''), 
            COALESCE(project_date, CURDATE()), 
            COALESCE(project_type, 'other'), 
            created_at
     FROM projects",
    
    // Migrate all images to unified table
    "INSERT INTO project_images (project_id, image_title, image_path, image_type, created_at)
     SELECT project_id, COALESCE(image_title, ''), image_path, 'main', created_at
     FROM mainimages",
    
    "INSERT INTO project_images (project_id, image_title, image_path, image_type, 
                                 display_order, created_at)
     SELECT project_id, COALESCE(image_title, ''), image_path, 'gallery', 
            COALESCE(display_order, 0), created_at
     FROM carouselimages"
];

// 3. Verify data integrity
$verificationQueries = [
    "SELECT COUNT(*) as project_count FROM new_projects",
    "SELECT COUNT(*) as image_count FROM project_images"
];