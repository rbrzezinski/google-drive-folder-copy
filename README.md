Copy whole Google Drive folder tree getting new files and folders IDs.

## How to use?

1. Create service account in Google Cloud Console.
2. Save auth JSON file at path `./service-account.json`
3. Grant write permisions at source and destination folder to email `name@name.iam.gserviceaccount.com` (replace with your real email address).
4. Provide source and destination folder IDs in begining of `run.php`.
5. Run command `php run.php > results.txt`