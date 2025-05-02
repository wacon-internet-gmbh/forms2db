 Save TYPO3 Form Data with "forms2db"

With the system extension **"forms"**, creating forms in TYPO3 is very simple. Unfortunately, by default, the data entered by users is **not** saved. This issue is resolved by our custom extension **"forms2db"**.

## Installation

Install the extension via Composer:

```bash
composer require wacon/forms2db
```

## Setup

In the TYPO3 backend:

1. Navigate to **Admin Tools > Maintenance**
2. Run:
   - **Analyze Database Structure**
   - **Flush TYPO3 and PHP Cache**

## Usage

After installation and setup, the following features are available:

### 1. New Finisher: "Save the Mail to the Database"

In your TYPO3 **forms**, the finisher **"Save the Mail to the Database"** becomes available. This allows user-submitted data to be stored in the database.

### 2. Module: "Forms results"

A new backend module **"Forms results"** is added:

- Export submitted data in **CSV format**
- Process the data in external tools (e.g., Microsoft Excel)

### 3. Data Deletion

You can manually delete stored form entries via the **Forms results** module.

### 4. Automatic Deletion (Privacy Recommendation)

To comply with data protection laws (e.g., GDPR), we recommend setting up **automatic data deletion** using the TYPO3 **Scheduler**.

Use the following scheduler task:

- **Task name:** `Table garbage collection`

This will regularly clean up stored form data according to your configured retention policies.

For more information see:
https://www.wacon.de/typo3-service/forms2db.html
