# Quran Recitation Feature - Implementation Summary

## Overview
Added a feature to track and display how much Quran a student has memorized (in pages, words, and verses) within weekly and monthly periods, with individual breakdown by homework type.

## Changes Made

### 1. Backend Changes

#### `classes/Reports.php`
- Added `getStudentRecitationQuantity()` method
  - Calculates total words, verses, lines, and pages recited by a student
  - Only counts homework types where `different_types = 'quran'`
  - Supports both weekly and monthly period filtering
  - Returns: total_words, total_verses, equivalent_lines, equivalent_pages, total_homework_items
- Added `getStudentRecitationQuantityPerType()` method
  - Calculates recitation quantity for EACH individual homework type (maadi qareeb, maadi ba3eed, gadeed, etc.)
  - Dynamically finds all homework types where `different_types = 'quran'`
  - Returns an array with quantity stats per type
- Updated `getStudentReport()` method to include both overall and per-type recitation data

#### `api/get-student-recitation.php`
- New API endpoint for fetching recitation quantity
- Parameters: `student_id`, `period` (weekly/monthly)
- Returns JSON with recitation statistics

### 2. Frontend Changes

#### `views/admin/reports/student-reports.php`
- Added new card for "Quran Recitation" in the performance overview section
- Added new "Quran Recitation by Type" section showing individual homework types
- Displays pages recited and supporting details for each type

#### `assets/js/student-reports.js`
- Added `renderRecitation()` method
  - Displays overall pages, verses, and words count
  - Adds performance level classes (excellent/good/fair/poor) based on pages recited
- Added `renderRecitationPerType()` method
  - Dynamically renders cards for each Quran homework type
  - Shows pages, verses, words, and assignment count per type
  - Color-coded performance levels
- Integrated recitation rendering into the main report flow

#### `assets/css/student-reports.css`
- Added styling for `.recitation-pages` and `.recitation-details`
- Added styling for `.recitation-types-section` and `.recitation-type-card`
- Color-coded gradient borders based on performance (excellent/good/fair/poor)
- Responsive design support

## How It Works

### Data Flow
1. Student homework with type 'quran' is stored in `session_homework` table
2. It references `homework_grades` which contains Quran chapter details (sura, from, to)
3. The system joins with `quran_verses` table to count words and verses
4. Calculations:
   - **Equivalent Lines** = Total Words / 15
   - **Equivalent Pages** = Total Words / 300

### Usage
- Access via: `/views/admin/reports/student-reports.php?student_id=X`
- Toggle between weekly and monthly views
- Displays on report dashboard alongside performance and attendance

## Database Schema Used
- `session_homework` - Student grades and homework records
- `homework_grades` - Quran assignment details (quran_from, quran_to, quran_suras_id)
- `homework_types` - Homework type categories (filters by 'quran' type)
- `quran_verses` - Quran verses with word counts
- `quran_suras` - Quran chapters/suras

## Performance Thresholds
- **Excellent**: ≥10 pages
- **Good**: ≥5 pages
- **Fair**: ≥2 pages
- **Poor**: <2 pages

## Page Calculation
- **Lines per page**: 9 words (1 line = 15 words, adjusted for readability)
- **Pages**: Total words ÷ 135

## Per-Type Breakdown
The system automatically detects all homework types where `different_types = 'quran'` and displays:
- Individual quantity for each type (e.g., maadi qareeb, maadi ba3eed, gadeed)
- Total pages, verses, and words per homework type
- Number of completed assignments per type

## Dynamic Type Detection
The feature dynamically queries the database for homework types with `different_types = 'quran'`, making it flexible for adding new Quran homework types without code changes.

