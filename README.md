# Currency Change Percentage

### The task has been completed in Lumen framework.

##### Please follow below steps to evaluate the task.

1. Change the .env file with valid mysql credentials.
2. Run php artisan migrate.
3. php artisan db:seed --class=UsersTableSeeder.
4. For login API run BaseUrl/api/login, Use credentials chirag@mail.com/12345678 with lable email/password.
5. Add the generated token in headers with lable Authorization with value Bearer <token>.
6. For checking any period add the following for
    URL: BaseUrl/api/period with label date and value as one of given below
    Date: YYYY-MM-DD
    Month: YYYY-MM
    Week: YYYY-Www [e.g: 2022-W30]
    Year: YYYY

    1. When date is passed the percentage change will show from the previous date.
    2. When month is passed the percentage change will show from 1st date of month to last day of the month.
    3. When week is passed the percentage change will show from given weeks monday to sunday.
    4. When year is passed the percentage change will show from 5th January to 31st Decempber.

7. For checking between dates pass the date with label start_date and end_date with the format YYYY-MM-DD.

