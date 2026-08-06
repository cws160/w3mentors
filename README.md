<p align="center"><a href="https://onlinetutoring.w3mentors.com/" target="_blank"><img src="https://onlinetutoring.w3mentors.com/images/w3mentors-logo.svg" width="400"></a></p>


**<p align="center">Version RV-4.4</p>**

> **New stack:** This project now includes a **Laravel API** (`backend/`) and **React frontend** (`frontend/`) with no license or IonCube required. See [MIGRATION.md](MIGRATION.md) for setup and migration status. The legacy W3Mentors PHP app below is deprecated.

## About W3Mentors

w3mentors is a ready-made software which provides pre-recorded courses, online tutoring and consultation platforms based on video conferencing functionality. The eLearning software is integrated with a suite of interactive features to enable smooth navigation and workflows for both learners and tutors. As a fully customizable solution, w3mentors can be deployed to cater to many common and vital functionalities in platforms like Verbling, Preply, Italki, and Cambly.

w3mentors powered online tutoring or consulting platforms incorporate a full-blown marketing module instrumental in gaining wider reach and business promotion.

## Features

- **UX/UI Enhancements**: Experience a fresh, intuitive look with W3mentors v4.4! A revamped homepage, dynamic content blocks, enhanced search filters, and an improved Calendar UI make navigation smoother and scheduling effortless.
- **Edit Published Courses**: Tutors can now edit their published courses within an admin-defined duration. With an approval-based system, updating course details is now seamless and hassle-free.
- **New Admin Report**: Tutor Hour Report for performance tracking, Wallet Balance Report for financial oversight, and Teacher Payout Report for streamlined payments. 
- **Multi-Level Subject Category:** The platform now supports multi-level subject categories to handle the subjects/languages/services more effectively on the platform. 
- **Order Detail Download/Print:** Downloading invoices is now available for Users and Admin. They can also print order receipts directly from the orders section under their dedicated dashboards.
- **Abusive Word Handling:** This feature allows Admin to create a directory of restricted words to protect your platform from abusive comments, promoting a safer and more respectful environment for users. The Abusive words will be detected in the group chat, reviews, forum comments, and one-to-one messages. 
- **Subscription:** This feature enables platform admin to create subscription packages for learners. Learners can choose to purchase these packages or proceed with the standard checkout process. The feature can be activated or deactivated based on business needs.
- **Quizzes:** This impressive new feature will allow the platform Teachers to add different types of questions, set time limits, and decide how quizzes are scored. Quizzes can be linked with courses, classes and one-to-one lessons with a limit on how many times a quiz can be attempted. With built-in reports, it’s simple to track how learners are doing and help them improve. 
- **12/24 Hour Time Format:** This feature allows the flexibility to choose between a 12-hour or 24-hour time format for the platform as per the business requirement.
- **MUX Integration:** MUX is a powerful video streaming platform that enhances video delivery and playback capabilities. By integrating MUX, platforms can provide users with high-quality, low-latency video experiences while ensuring scalability and reliability. Note: This is introduced for the courses module on the platform.
- **Enable/Disable Group Class/Packages:** This is a code-level setting introduced to disable group classes/packages on the platform based on business requirements.Note: The module can be disabled during the initial setup and re-enabled later. However, once enabled, it cannot be disabled again due to complexities related to orders and statistical data.
- **Payment Gateways:** With the existing refined structure of the plugin system we have integrated two new payment methods i.e Mpesa and Payfast. Plugins can be attached and detached as per the client's need.
- **Meeting Tools:** Our plugin based structure allows us to add or remove the meeting tools as per the client’s requirements. Apart from the existing tools like Jitsi, Lesson Space, Zoom and AtomChat/CometChat, the system now has introduced "Pencil Spaces meeting tool" as a collaborative tool for interactive online sessions, enhancing the virtual learning experience with real-time drawing and brainstorming tools.
- **Group Chats:** This feature allows the teacher and students of a specific group class to interact within the group. They can also share pictures and files through the group chat. Once a student purchases the group class, they will be automatically added to the group chat.
- **Export Feature:** The admin shall be able to export the data for all modules in a CSV format. Import is also allowed for the system labels allowing admin to customise the content easily.
- **Admin Earnings:** Admin can review the transaction history which includes the earnings from commissions, income from cancelled orders, and any additional sources of revenue that may be introduced in the future. The admin will have the ability to review the total amount earned as profit related to lessons, classes, or courses.
- **New Admin Theme:** Introducing a new Admin Theme to improve the overall look and feel of the admin dashboard. Additionally, added a Help Section for every module to provide assistance and guidance.
- **Offline Classes and Geo Location:** Students will be able to engage with teachers outside or offline the platform by reserving the slot through the standard booking procedure. The enhanced filters allow users to search nearest teachers by using Geo Location.
- **Refer and Earn:** This program encourages users to get their friends registered on the platform by sharing the referral link across the various platforms. Both the referee and the referer receives the rewards on the signup as well as on first purchase.
- **2 Way Calendar Syncing:** Now, users can synchronise their Google Calendar with the platform's calendar to manage their availability. As a result, the platform's calendar and Google Calendar will display events based on both the calendars (platform calendar and Google Calendar).
- **Affiliate module:** Affiliates users are also allowed to register on the platform. They can refer users by sharing their unique affiliate link. Affiliates will get signup as well as purchase commission whenever a referred user registers or completes the sessions.
- **Online Tutor:** The system tracks activity of teachers and shows online/away status to users on listing and detail pages. Users have an option to filter currently online tutors.
- **Featured Tutor:** The admin can mark the teachers as featured on the platform. Featured teachers are displayed at the top of the teacher listing and detail pages with a verified badge on the profile.
- **Session Recording:** Enables auto-recording of the session, which users can replay in their dashboards. Users can click and play the recorded sessions. It is available on Lessons & Classes listing and detail/view pages.
- **Admin Manageable & Hourly Prices:** Admin or Teacher can manage subject prices(hourly). If teachers manage the prices, the admin can set maximum and minimum price limits for each subject. Admin can also set hourly prices for each subject.
- **Login with Apple:** This feature allows users to log in and sign up on the platform using an Apple account.
- **Google Tag Manager:** Allows admin to manage all your website tags without having to edit codes. Use tag manager to add and update Google Ads, Google Analytics, Floodlight, and third-party tags.
- **Text Translator API:** Auto-translation of Labels, content blocks, and content pages in other languages under the Admin dashboard. Teacher Biography, Group Classes, and Packages content on the front end.
- **Global Currency formats:** This feature will provide the admin the ability to manage currency formats according to global standards. Reference doc: https://docs.microsoft.com/en-us/globalization/locale/currency-formatting
- **Automate Meta tags:** Auto Meta tags for Teachers/Classes/Packages. The system will auto-create the Meta Title, Keywords and Description, OG Image, and so on for SEO. However, the admin can still update and edit the details. 
- **Mobile Applications Supported:** The system now supports dedicated mobile applications (iOS and Android both), enabling users to access the platform features through their mobile devices.
- **Courses:** This feature allows teachers to create a complete course and sell it online. Courses consist of sections, lectures and resources, which further include videos, images and text. Teachers may offer certificates which will be issued by the platform upon course completion.
- **Courses Search:** Courses lists with enhanced search filters provide the easiest way to find the required courses and book them in a single popup window effortlessly. The search is designed using the highly optimised search models of w3mentors V3 to handle the large data sets with outstanding performance.
- **Resources:** Resources are a data set of additional study material that can be managed separately by the teachers. Bulk uploading of files including pdfs, docs, images and zips, is allowed and can be further attached in the course lectures.
- **Certificate:** Admin has the ability to customise the certificate content and background image according to their platform.
- **Home Page Content:** Our home page content is tremendously manageable in terms of content, design, visibility and display sequence. Each content block is manageable from the admin panel and can be repositioned in any order.
- **Teachers Availability:** We have two availability types: General Availability and Weekly Availability. Managing tutors availability for user specific timezone and conversion to convert system date according to user’s timezone was a challenging task for 70+ countries supporting DST(Daylight Saving Time). Completely redesigned the database structure, and have also added another table to maintain exact availability of the user(which Convert general to weekly availability) which inturn solved the time difference we face in countries with DST enabled.
- **Teacher & Group Class Search:** A major update on performance optimization to Teacher Search and Group Classes Search has been performed by optimizing database structure & queries and has created search models for teacher and class search by extending w3mentors base search model.  Redone complete Search form design to improve UX/UI.
- **Seamless Checkout:** Checkout is now on a single popup which is used for different order types. One step checkout has been used for booking Group class, Group Class package, Adding Money to Wallet and Purchasing Gift Cards.
- **Inline & Unlimited Scheduling**: Till W3Mentors V2.4 We only had single session scheduling after booking. Now Learners can check Tutor’s exact availability and schedule unlimited sessions before booking a Tutor or making any payment.
- **Fully Manageable Search Engines friendly URLs**: URL rewriting allows URLs to be more easily remembered by the user. Option to replace the complete url path with any other desired url is possible now. Ability to add different custom URLs for different languages.
- **User Transactions:** Categorized user's Transactions for each type of order payment, Student Refund, Teacher Paid, Money Withdrawn, Money Deposit, Gift Card redeemed, Support credit and Support debit. Ability to calculate Total IN and OUT of a user’s money in the platform. We can have every type of report for every type of user.
- **Reports Sales & Settlements:** Database structure has been changed completely for the orders section and V3 has the ability to provide any type of reports. As of now we have Lessons Top Languages, Classes Top Languages, Teacher Performance, Lesson Stats,  Sales Report and  Settlements Report. Admin can check everyday’s Gross Sale, Discount, Net Sale, Paid to Teacher and Refunded to Students.
- **Group Classes:** Group Classes are now treated as separate entities, It may be a pre scheduled event by the Teacher and any Learner/Student can book and join it. Earlier it was mixed with one to one lesson/session and calculation of Teacher payment on success and refund to student was complex.
- **Manageable Themes:** Theme management is available with six basic colors Primary Color & Inverse Color,  Secondary Color & Inverse Color And Footer Color & Inverse Color.
- **Email Templates:** Updated all Email templates with new HTML structure. Email save and preview option available now which Admin can use to preview email templates. Header and Footer for all email templates are manageable at a single place.
- **Manage Orders:** Manage all 6 types of orders and order’s status with all details. Keyword Search for Main and all other order types. More detail has been added in the orders table which makes reporting more efficient. Order Id, User Name, Order Type, Items, Total, Discount, Net Total, Payment, Status, Pay Method, Datetime
- **Learner & Teacher Dashboard:** Separate dashboard for Learners & Teachers. New Base Search model has been created to list Lessons, Subscriptions, Group classes, Order classes, Package classes, Gift Cards, All Orders and Report Issues.Note: Base Search model is well structured and designed to make searches for bulk data.


## System Requirements

The following technical requirements are needed to set up w3mentors:

| SrNo | Software | Version | Help|
| ------------ | ------------ | ------------ | ------------ |
| 1 | Ubuntu x86, x86-64 | 20.04+  | [Ubuntu](https://ubuntu.com/)  |
| 2 | Web Servers | Apache 2.4.x | [Apache](https://httpd.apache.org/)  | 
| 3 | PHP Version | 8.1.x |  [PHP](https://www.php.net/) | 
| 4 | MySQL Version | 8.0.x | [MySQL](https://www.mysql.com/)  | 

## Required PHP Extensions

    GD with Free Font support, Zlib with zip support, JSON, DOM, cURL enabled, Mbstring enabled, Iconv enabled, Fileinfo enabled, Calendar enabled, Ioncube Loader, PHP Composer, Memory_limit 64M+

## Required MySQL Settings

    [mysqld]
    sql-mode=NO_ENGINE_SUBSTITUTION
    log_bin_trust_function_creators=ON

## Version History

- **Version 4.4**: Enjoy a fresh UX/UI with an intuitive homepage, dynamic content blocks, better search filters, and an improved Calendar UI for effortless navigation. Tutors can now edit published courses within an admin-defined window, ensuring seamless updates via an approval-based system. Plus, new reports—Tutor Hour Report, Wallet Balance Report, and Teacher Payout Report—empower admins with valuable insights for performance tracking and financial management.
- **Version 4.3**: Introduced features like Subscription, Quizzes, Multi-Level Subjects, Order Detail Download/Print, Abusive Word Handling. Also, the platform now supports 12/24 hour datetime formats and provides manageability to enable/disable the Group classes module. Additionally, MUX and Dropzone have been integrated for enhancing videos uploading and rendering experience.
- **Version 4.2**: W3Mentors now provides Offline Classes with Geo Location search and includes new features like Group Chats, 2 way calendar syncing, Refer and Earn and Affiliate module. New enhanced theme for admin with export feature available for all modules.
- **Version 4.1.2**: Enhancements in pre-recorded courses module. Integrated VdoCipher for uploading private videos instead of public YouTube urls. Integrated Jitsi meeting tool.
- **Version 4.1.1**: Enhancements for the deprecated module. Updated Zoom meeting tool SDK and integrated the Google Analytics 4 version.
- **Version 4.1**: Online Tutor, Featured Tutor, Session Recording, Admin Manageable & Hourly Prices,  Login with Apple, Google Tag Manager, Text Translator API, Currency conversion(Fixer API), Globalization of Currency formats, Auto Meta tags for Teachers/Classes/Packages, Mobile Applications Supported and  UI/UX improvements and Bug fixes.
- **Version 4.0**: Pre-recorded courses module with sections and lectures consisting of videos, text, images and additional attached resources. Provides certificates optionally that are issued by the platform on course completion.
- **Version 3.0**: W3Mentors V3’s software architecture is re-designed to handle and manage high user data, While taking care of quality code and system performance
- **Version 2.4**: Theme management for Website and Email Templates.
- **Version 2.3**: Added new features Multiple Price Slab & GDPR - Right to Erasure.
- **Version 2.2**: Payment Gateways(Paystack,PayGate), Progressive Web Apps (PWA), Session Duration Management. New Theme and User dashboards
- **Version 2.1**: Added new features Zoom, Lessonspace, Google Analytics and PayPal Payouts

## Documentation & Updates

- [Recent Updates](https://www.w3mentors.com/recent-updates.html) Recent Versions and Updates.
- [Documentation](https://www.w3mentors.com/documentation.html) Find All The Resources At One Place To Help You Setup Your Online Tutoring & Consultation Platform Successfully.

## Installation Instructions

1. **Prerequisite**

	- You are able to access the server using **SSH**.
	- You have installed **Apache**, **MySql** and **PHP** on linux server.
	- You have installed ioncube loader compatible to **Fatbit Library V2.5**

2. **Clone W3Mentors**

    Install git and clone Clone W3MentorsV4.4 to your root directory.

		sudo apt install git
		git --version
		cd ./path/to/your/rootdir
		git clone git@github.com:your/repository.git .
		
    **Note:** OR if you have script files, Upload script files to the root directory and continue.

3. **Copy setup-files files**

    Copy `conf` `user-uploads` `public` `mbs-errors.log` and everything from `setup-files` to root directory.

		cp -r setup-files/* .

4. **Install Fatbit Library**

    Download and install Fatbit library and unzip to the library directory and then remove `core.zip`.

		wget http://fatlib.4livedemo.com/download/v2.5/core.zip
		unzip core.zip -d library
		rm core.zip

5. **Download user-uploads**

    Download and unzip user-uploads to the root directory and then remove `user-uploads.zip`.

		URLs:
		Online Tutoring https://download.w3mentors.com/4.4/tutoring/user-uploads.zip
		Elearning       https://download.w3mentors.com/4.4/elearning/user-uploads.zip
		Business        https://download.w3mentors.com/4.4/business/user-uploads.zip
		Wellness        https://download.w3mentors.com/4.4/wellness/user-uploads.zip

		wget --http-user=USERNAME --http-password=PASSWORD <URL>
		unzip user-uploads.zip -d .
		rm user-uploads.zip

6. **Install Dependencies**

    Never use composer update, It may break your application. It will update versions of packages to be installed and may not be compatible with your system.

		composer install

7. **Import Database**

    We have `sample.sql` and `blank.sql` databases in the `database` directory. Databases can be imported as per requirement.

		mysql -u mysqlUsername -p mysqlDatabase < database/sample.sql
		mysqlPassword

	**Note:** For niche specific installations, download the required database from the following links, import and remove the file.

		Elearning       https://download.w3mentors.com/4.4/elearning/sample.sql
		Business        https://download.w3mentors.com/4.4/business/sample.sql
		Wellness        https://download.w3mentors.com/4.4/wellness/sample.sql

		wget --http-user=USERNAME --http-password=PASSWORD <URL>
		mysql -u mysqlUsername -p mysqlDatabase < sample.sql
		mysqlPassword
		rm sample.sql

8. **Connect Database**

    Configure MySQL database connection settings in `public/settings.php`

		nano public/settings.php
    `settings.php` file will look like below

		<?php
		define('CONF_WEBROOT_FRONTEND', '/');
		define('CONF_WEBROOT_DASHBOARD', '/dashboard/');
		define('CONF_WEBROOT_BACKEND', '/admin/');
		define('CONF_DB_SERVER', 'localhost');
		define('CONF_DB_USER', 'mysqlUsername');
		define('CONF_DB_PASS', 'mysqlPassword');
		define('CONF_DB_NAME', 'mysqlDatabase');
    Save and Exit (Ctrl+x and Shift+y)

9. **Grant Permissions**

		chmod -R 777 user-uploads
		chmod -R 777 public/cache
		chmod 777 mbs-errors.log
		chmod 777 public/error_log
		chmod 777 public/robots.txt

10. **Setup Cron Job**

		crontab -e
		*/5 * * * * /usr/bin/curl  -s https://yourdomain.com/cron > /dev/null 2>&1
    Save and Exit (Ctrl+x and Shift+y)

    **Note:** The command may vary depending upon the products and their version.

11. **Custom Configuration**

    Update `{root}/conf/conf-common.php` as per your requirements

		define('SEARCH_MAX_COUNT', 10000);
		define('CONF_DEVELOPMENT_MODE', false);
		define('CONF_USE_FAT_CACHE', true);
		define('ALLOW_EMAILS', true);
	
    **Note:** Please ensure that SSL is enabled at database level.

