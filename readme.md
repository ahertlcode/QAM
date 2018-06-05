Quick Application Maker Readme file
===================================
After downloading the application on github, extract and place the application in on your development machine localhost server folder where you have other projects. then follow the following steps to create an entire application from start to finish by a few command from the commandline.

## Direction

open the QAM application folder and open the terminal at that position of navigate your way into the QAM application folder through the commandline.

QAM assumes you already have a mysql database designed and your database tables have been well laid out. What QAM will do is accept your database parameter to scaffold the entire application that will perform the basic CRUD given your database tables and will also generate the neccessary reports depending on the command you decides to run on the database.

The generated code follows MVC design paradigm and are easy to edit to you can add business specific logic to your application.

If your database has been properly designed you will not need to design any part of your views that is the frontend of your application. Though you may have to write a few php, css and javascript code before your application is release into production but they will only be neccessary to add business logic to your application.
## Command
`php maker --app --db="host:param1,db:param2,user:param3,password:para4" --exclude="tables" --report="report_formats" --exclude-null`

## param1 -
this is the address of your database server. this will have to be "localhost" if your database is on the development machine. It could also be the IP or url of your database server.

## param2 -
this is the database name for the database instance you want to develop application for. e.g sakila or northwind e.t.c

## param3 -
is the username for the database instance you want to make use of.

## param4 -
is the password of the database user that has a DBA access to the database instance you want to use.

## tables -
is the comma seperated list of tables you which to excempt from your application design. If there are table you use for processing purpose or for keeping some system constant that you wouldn't want to have a frontend design for include them in the list so they can be excluded from the design.

## report-formats -
here you specify the various format you want for your application report. e.g table, list, card, timeline. This are all the report formats available at present. You can have a comma seperated list of them or just one of them depending on what you want to system to make use of in reporting from your database tables.

## --exclude-null
is optional when added to your command it exclude all the nullable column of your database tables from the form design i.e. it will not include the all columns that has the the Null properties set to YES in your html form.

## Others
As at the time of this first version the frontend library available on QAM is bootstrap. It also came bundled with ckeditor 4 for those that will like to create a blogging solution with it. Also bundled with it is jQuery, Angularjs and popperjs for use with bootstrap, though bootstrap require jQuery too but it was just included for that purpose.