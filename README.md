### Bookon Bankend setup guide

## Criteria
Bookon is currently running on local machine supported by XAMPP. Therefore, you need to have XAMPP installed before starting the setup.


## Setup steps
If you are ready, follow these steps to run the project:
# Step 1
Clone the repositories of back-end (this repo) and front-end (bookon-fe) to XAMPP htdocs folder under a parent folder named "bookon". The folder structure after cloning should look like this:
```
    htdocs
    ├── bookon
    │   ├── bookon-fe
    │   └── bookon-be
    └── ...others
```
# Step 2
Run XAMPP, then start MySQL Database and Apache Web Server.
# Step 3
Access http://localhost/phpmyadmin/ to access the local MySQL database. Then click "Import" ("Nhập") to import compressed zip file of the sql scripts given.
After the importation, you should see several a database name 'web_bansach' with several related tables.
# Step 4
Now your backend is ready. You can start calling api from front-end.
The api should follow this base structure:

    http://localhost/bookon/bookon-be/api/<module>/<...>

e.g: https://localhost/bookon/bookon-be/api/auth/login

## PS
After cloning front-end repo, you should install related node modules before starting build the project. You can start with:
```
npm install
npm run dev
```
