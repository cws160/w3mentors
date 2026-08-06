## RV-4.4 (28-02-2025)

Features:

	- New admin reports: Wallet balance report, teacher payouts report, hours taught report
	- Edit published courses

Enhancements:

	- UI/UX enhancements.

Fixes:

	#090405 - Settings section design is distorted if there are more languages in the platform
	#090640 - Zoom Pro License not getting revoked in case of Autocomplete 
	#090407 - Language switching automatically if lang code setting is enabled. 
	#090255 - Teacher is able to book his own lesson. 
	#090003 - The size of message box to be increase or adjusted as per added text 
	#089632 - App labels import changing lettercase of keys


## RV-4.3 (20-11-2024)

Features:

	- Subscriptions
	- Quiz module
	- Group class enable/disable
	- Order Detail Download Feature
	- MUX Integration
	- Multi-level Subject/language Categories
	- 12-24-hours-format
	- Abusive Word Handling
	- Mpesa & PayFast Payment Gateway

Enhancements:

	- SEO Updates and Discussions.
	- Proficiency levels manageability.
	- Future dates for end year in the experience tab on teacher registration.
	- Auto-cancellation settings for pending orders.
	- Certificates preview option in listing.
	- Wallet recharge minimum amount setting.
	- Retain unread notifications when Admin access user profiles.
	- Payment method search on "My Orders" page under the user's dashboard. 
	- Dropzone.js for videos uploading in chunks. 
	- reCAPTCHA integration on Sign-up forms.
	- UI Enhancements: online/offline session flags added to differentiate.
	- Disaplyed sender's name in group chat.
	- Confirmation for user status updates at Admin end.
	- Twitter icon updated.
	- Added hover functionality to the admin dashboard graph stats to show detailed or exact statistics. 
	- Removed the currency sign from the list in exported files to simplify calculations in the spreadsheets.
	- Adjusted the permission hierarchy for the Manage Permission Module feature.

Fixes:

	#088599 - LBL_Cancelled has impact on two areas
	#088097 - Update on the teacher registration form related to end year
	#088597 - Language specific slider image is not reflecting
	#087639 - CMS pages content is not adjusting the default layout of the page
	#087355 - The auto-translate feature is not working for the description text of the teaching languages
	#087554 - The minimum limit at the time of wallet recharge can be removed or setting can be provided under admin
	#087559 - If the offline toggle button is enabled during checkout, it will remain active for all teachers even after the pop-up is closed.
	#085599 - Need to increase price range limit
	#086875 - Fatal errors and warnings are coming wherever the price is coming if admin deactivates the site currency
	#086438 - currency id does not work on payment if disabled from admin end
	#083078 - Anyone can access or download users' photo IDs and credentials through ids
	#085248 - Content page images not loading in Spanish
	#085755 - A forbidden error occurs when joining the lesson/class using the Zoom meeting tool
	#085501 - Duplicate meta identier issue with classes and courses
	#085496 - Unpublished course is accessible on detail page
	#085435 - adjusted lognPressDelay settings
	#084966 - Footer icons not visible if changing the footer background
	#084693 - Fatal error when the timezone is not found correct
	#083860 - All records are not getting exported in all orders exported file
	#084392 - 404 error page coming after publishing the Question on the forum and clicking on the View icon
	#084340 - User language changing when placing an order.
	#084325 - Admin footer is not aligned to the bottom if page content length is less
	#084246 - Label keys are generating in the languages other than English
	#083799 - Teacher profile complete/incomplete status icon is overlapping switching account popup issue fixed
	#083743 - No response when submitting a withdrawal request
	#083091 - Lesson schedule email content is not coming properly
	#083352 - Hong Kong country missing

## RV-4.2 (18-03-2024)

Features:

	- Affiliate module
	- Offline Sessions with Geo Location Search
	- Refer and earn reward points module
	- Export features for all modules in admin
	- Lesson cancellation fee to teacher and admin
	- Group Chats: Chats for group classes/package
    - Admin earnings txn per lesson/class/course

Enhancements:

    - Admin Theme
	- Two Way Syncing Google Calendar
	- Zoom with and without ISV
	- Updated copyright signatures
	
Fixes:

	#080240 - Getting Grabbled words on the certificate in case of emojis and Chinese letters
	#076734 - The blog category name is not coming in the latest blog section of the homepage if only its identifier is added
	#080188 - Dashboard pages showing 404 with lang code settings
	#073877 - Apply to teach page is not working with Arabic language
	#079674 - Replacement variable's values are not converting in emails
	#079487 - Showing error if teacher name is added in different language
	#079339 - Blog listing should be in descending order for Admin under Blog Post section
	#079336 - Pagination issue on teacher listing page
	#079327 - Spanish content not rendering properly on checkout
	#078959 - Unnecessary spacing for FAQs in IOS devices
	#078478 - A random banner image showing when creating a new group class
	#078407 - Content block background image is not manageable
	#078403 - Inactive FAQs are still visible on the apply to teach page
	#078110 - Course videos alignment is not correct
	#077990 - Special character are not rendering properly on home search
	#077535 - timezone undefined from js
	#077435 - Kindly remove the Zoom meeting tool and related things from the platform
	#076807 - video popup issue for mobile fixed
	#076810 - To remove cookies dependency from language change and to fix issue with disabling cookies preferences
	#076806 - To re-direct the learner to the orders sections, specifically, when the payment is made via Bank Transfer
	#076623 - No further action can be performed when Sub Admin logins without Admin Dashboard permission
	#076668 - Teacher profile page is loaded partially
    #076619 - 404 on wallet payment with subscription
    #082561 - Unable to delete blog categorie(s) that do not have any blog post linked with them
    #082403 - Processing message is shown twice
    #082242 - Day and months labels not converting into other languages.
    #082154 - Tutor lsting not coming properly as per the parameters defined.
    #082743 - Nigeria country missing
    #082407 - Back to top icon overlapped by chat icon


## RV-4.1.2 (16-10-2023)

Features:

    - Jitsi(JAAS) a new Meeting Tool
    - VdoCipher integration for course

Enhancements:

    - Updated policies for FB app updates
    - Fixes for mobile API zoom showing web login when joining meeting
    - Updated translator language code to lower case
    - Browse Tutors Background image can be updated from CMS section

Fixes:

    bug-073919 - Home page top subjects are coming according to the sold count. 
    bug-073918 - Language is changeing to English automatically. 
    bug-073486 - Few Issues related to emails template content 
    bug-073877 - Apply to teach page is not working with Arabic language. 
    bug-074245 - The icons are blurry once uploaded on the platform
    bug-074365 - The fixer API after configuration doesnot work. 
    bug-074367 - On every svg icon change, the platfrom shows broken links
    bug-074771 - Site stops working when another language is set Default and English is disabled
    bug-074773 - Language/Currency Switch Not visible when only 1 Language and Currency is Active 
    bug-074854 - More Courses on Course Detail Page Generate Invalid URL in case of Special Characters 
    bug-075488 - Courses on Teacher Details Page Generate Invalid URL in case of Special Characters 
    bug-075467 - Terms and condition link is not updating. 
    bug-075280 - Login Protected functionality issue on the header pages 
    bug-076217 - Need to fix the 'Enter' functionality in the messaging module 
    bug-076273 - My teacher's listing is not coming and a continuous loader is coming query update
    bug 076271 - Time is not coming as per the learner timezone in the messaging, notification and wallet screen
    bug-075907 - Showing errors in headers and footers on 404 pages
    bug-075984 - Special characters are not rendering properly in meta listing headings. 
    bug-076284 - Class settlement is not getting done if report an issue on the lesson with the same ID 
    bug-076098 - The teacher should receive a system notification if the student is scheduling the lesson at the time of chekout
    bug-076461 - Home-> Icon in the footer menu is showing Home text
    bug-076217 - Need to fix the 'Enter' functionality in the messaging module
    bug-076229 - Cross icon does not appear to delete the sent attachment to the user in the Chat window
    bug-076205 - Course search is not working when language code is coming in the URL 
    bug-075177 - Relevant error messages are not coming if Google Analytics related settings are incorrect
    bug-075301 - Notes take a random language When the user adds on a free trial lesson from the video call page
    bug-075591 - The Google Analytics section design is not correct on the admin dashboard page
    bug-075173 - The event is not recorded for booking and confirm order 
    bug-075264 - User is not able to book lessons with a Wallet payment
    bug-075174 - UI and spelling issues related to Google Analytics blocks
    bug-075178 - The data does not match on the admin dashboard and Google Analytics dashboard
    bug-075250 - A warning is coming for a teacher record in favorite teacher's listing

## RV-4.1.1 (12-09-2023)

    - Zoom meeting tool version upgrade
    - Google Analytics 4 version upgrade


## RV-4.1.0 (12-06-2022)

Features:

    - Login with Apple Account
    - Google Tag Manager Settings
    - Tutor Online & last seen status
    - Featured Teachers functionality
    - Hourly Prices for Teach Languages
    - Admin Manageable Languages Prices
    - Mobile Applications Supported
    - Lesson-space Session Recording
    - Globalisation of Currency formats
    - Microsoft Text Translator API
    - Auto Currency conversion(Fixer API)
    - Auto Meta tags for Teachers/Classes/Packages
    - Discussion Forum Module

Enhancements:

    - Blog post listing new design
    - Admin Settings Re-arrangement
    - Front-end DateTime format updates
    - Auto deletion of Old Sent emails log
    - User Redirection to Same Page post Login
    - Multiple Alerts & Notifications Management
    - UI/UX improvements and Bug fixes

## RV-4.0.0 (03-04-2022)

Features:

    - Admin->Course categories management
    - Admin->Certificate templates management
    - Admin->Course approval requests management
    - Admin->Courses listing with preview
    - Admin->Course orders listing
    - Admin->Refund requests management
    - Tutor Resources management
    - Course creation by teachers
    - Course curriculum creation
    - Course pricing and settings management
    - Course preview after creation
    - Course searching on frontend
    - Course listing & details
    - Course booking by learner
    - Course tutorial
    - Notes management by learners for purchased courses
    - Download resources attached to a course
    - Course completion and certificate generation
    - Course cancellation and refund to learner
    - Courses stats management
    - Courses SEO management

Enhancements:

    - Home page updates
    - Tutor dashboard design updates
    - Admin->Categorized content blocks management
    - Notifications updated to display multiple alerts
   
## RV-3.0.0 (28-06-2022)

Features:

    - Bank Transfer Payment Method
    - One on One Lesson Subscription
    - Group Class Packages
    - Scheduling in Order Flow
    - Multiple Lessons Scheduling
    - Theme Management

Enhancements:
   
    - Complete w3mentors platform has been refactored and upgraded to follow industry standard coding practices.
    - Settlement reports have been added to view the refunds, commission and teacher payouts.
    - Admin theme has been uplifted and the UI/UX has been improved by categorizing the features in the relevant menu.
    - Details regarding transactions, users and time have been added in order listing.
    - More details on the admin dashboard have been provided for a summarized view.
    - Group classes images as thumbnails and banners have been added in group classes for an aesthetic view.
    - Classes and lessons are now segregated as different entities for transactions and report purposes.
    - Email templates have been updated with new one’s introduced at various triggers.
    - Learners can also withdraw money from the wallet in case a balance is available.
    - Teacher & Group Class Search Filters on front end listings have been updated with new UI/UX.
    - Users will now get unread message notifications in case they miss a message.
    - Flashcard module has been updated to notes which will let learners take notes on the platform.
