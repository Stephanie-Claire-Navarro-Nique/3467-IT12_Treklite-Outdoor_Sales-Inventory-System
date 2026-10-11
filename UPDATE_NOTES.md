# Treklite - version 3 (built on the "updated" version)

## Setup
Existing project (keep your .env):   copy these files over, then
    php artisan migrate
    php artisan storage:link
    php artisan config:clear
Fresh database:                      php artisan migrate:fresh --seed
Sign in: owner@treklite.test / password   and   staff@treklite.test / password  (change them in My Profile)
Optional sample products:            php artisan db:seed --class=DemoDataSeeder

## What is new in this version
- My Profile (sidebar name): Owner and Staff change their OWN name, email and password.
- Staff Accounts (Owner only): lists STAFF only. The Owner's own details are in My Profile.
- Products: "Model" wording. In the size/color table the first column shows the Model (read-only, follows the Model field).
- Item codes (SKU) are automatic and no longer shown on screens or the invoice (the invoice shows size/color).
- Same size + color cannot be entered twice for one model.
- Sales Records: Export CSV (Owner, follows the filters).
- Sign-in limit: 5 wrong tries, then wait one minute.
- Invoice numbers can no longer collide when two sales are saved at the same time.
- Reports / Sales Records no longer crash on a mistyped date in the link.
- Seeder is safe to run again (it never creates duplicates).
- Stock counts are stored as type "count" (old "adjust" rows are converted by the migration).
