@echo off
cd /d "c:\laragon\www\rental-jas-main"
php artisan schedule:run


/// You can use Task Scheduler to run a batch script every minute:

1. Create a run-schedule.bat file in your project root:
   ```
   @echo off
   cd /d 
   "c:\laragon\www\rental-jas-main"
   php artisan schedule:run
   ```
2. Open Task Scheduler
3. Create a new task that runs every minute and executes this batch file
4. Set the task to run as a system account
5. Set the task to run at the top of the hour
