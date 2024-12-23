CHANGELOG for 1.1.x
===================

This changelog references any relevant changes introduced in 1.1 minor versions.

* 1.1.6 (2024-12-23)
    * Issue #508 #549 - Filter issue resolved for customer filtering.
    * Issue #552 - In agent activity option: date filter should be select correct date format.
    * Issue #524 - ticket is in trashed folder and we will reply from the admin then it should not send mail to the customer.
    * Issue #577 - Customer name edit from admin side on ticket view page, if we leave space in starting customer name is removed in ticket view.
    * Issue #587 - ticket is updated from the ticket list page flash message should be same of updated option.
    * Issue #582 - In agent option, the permission tab should be shown a privileges icon.
    * Issue #583 - If extra enter space leave in ticket type, group, and team description so ticket threads do not loads on ticket view page 
    * Issue #573 - In the search filter, if we space to leave in the start and last search filter not working.
    * Issue #702 #601 - system can't calculate kudos score.
    * Issue #594 - The ticket view page is not showing the proper date time format.
    * Issue #605 - If saved Replies sharing without any group and team to another agent or administrator so here shows same saved reply instead of the 403 page.
    * Issue #606 - When mail reply from collaborator side in agent and customer reply email template so collaborator email reply creates a new ticket
    * Teams not removing from edit agent page - resolved
    * Issue #665 - When upload txt file in ticket , total count of words attaching at the end of the file.
    * Issue #644 - On the agent side should not be showing the reports icon without given any agent activity privileges.
    * Issue #656 - In spam settings: If email added in spam so should not be ticket created from the admin end.
    * Issue #656 - In spam settings: If email added in spam so should not be ticket created from the admin end.
    * Download link correction for ticket.
    * Initial thread opening issue if multiple emails in cc or collaborator.
    * Microsoft redirect URL update.
    * Lang select snippets position issue resolved on dashboard.
    
    Features:
    * Microsoft modern app support added.
    * Added option for select and save country for a ticket.
    * Round Robin Ticket assignment option added.
    * Showing customer email along with name in side filters ticket list.
    * In case of multiple attachments now added cross button for each attachment,
    So that user can remove a particular attachment.
    * Attachments renaming true for security purpose.

* 1.1.4 (2023-06-13)
    * Update: Render package version number dynamically

* 1.1.3 (2023-06-12)
    * Update: Dropped dependency on uvdesk/composer-plugin in support of symfony/flex
    * PR #638: Add RTL support for supported locales (ar) (Abhi12-gupta)
    * Update: Redefined workflow events & action, updated workflow triggers for improved compatibility support
    * Update: Correctly format email address collections for addresses with both name & address details while sending emails
    * PR #615: Use ticket.createdAt instead of initialThread.createdAt for displaying created at timestamp in ticket details (Komal-sharma-2712)
    * Bug #604: Error in deleting agent accounts from members dashboard (Komal-sharma-2712)
    * Bug #622: Added viewport initial-scale in layout.html.twig file (Komal-sharma-2712)

* 1.1.2.2 (2023-02-14)
    * Feature: Add formatted function to render timestamp based on available user locale preferences

* 1.1.2.1 (2023-01-31)
    * Fixes: Resolve issues while saving custom fields on a ticket

* 1.1.2 (2022-11-02)
    * PR #614: Changes to custom-fields app integration (Komal-sharma-2712)

* 1.1.1 (2022-09-13)
    * Bug #584: Fix sidebar from flickering during page reload & resize (vipin-shrivastava)
    * PR #576: Entity reference updates; Enable locale change; Set global timeformat; attachment is not going within ticket assign email workflow, sidebar flicker issue (vipin-shrivastava)
    * Bug #569: Wrong saved replies pagination results (papnoisanjeev)
    * PR #566: List ticket types in alphabetical order and display ticket type name instead of description (papnoisanjeev)
    * PR #192: Update BootstrappingProject.php (WebmaticMerseburg)

* 1.1.0 (2022-03-23)
    * Feature: Improved compatibility with PHP 8 and Symfony 5 components
    * PR #530: Updated ticket type and saved replies form validation criterias (vipin-shrivastava)
    * Bug #521: Only allow modification of privileged roles if acting agent has the same role enabled (vipin-shrivastava)
    * Bug #519: (514, 515, 517, 518) Resolve issues with ticket mass update, *ticket.link* ticket placeholder value, and agent account role update; remove manage group saved reply privilege settings (vipin-shrivastava)
    * Bug #512: Correctly format and process email recipient collections for outbound agent emails (vipin-shrivastava)
    * Bug #510: Add cross-site scripting checks uploaded .svg assets (vipin-shrivastava)
    * Bug #509: Throw NotFoundHttpException instead to render *404 Page Not Found* error message when ticket is not found (vipin-shrivastava)
    * Bug #506: Ticket placeholder *ticket.threadMessage* referencing the most recent thread should not be of type *note* (vipin-shrivastava)
    * PR #505: Change ordering of dashboard navigation segment items to render tickets before reports (vipin-shrivastava)
    * Bug #497: Add past ticket reference ids to mail headers for outbound customer emails (PeopleInside)
    * Bug #493: Disable user account based on defined roles instead of all disabling all instances of user account (vipin-shrivastava)
    * Bug #491: Editor resize issue while creating ticket from profile dropdown (vipin-shrivastava)
    * Bug #490: Use editor when creating ticket from profile dropdown initialize with agent signature details if available (vipin-shrivastava)
    * Bug #486: Update ticket message reference ids while sending outbound emails (vipin-shrivastava)
    * Bug #485: (476, 475) Unexpected error applying prepared responses on tickets assigned to a group, and issue rending inline base64 encoded images in mail contents (vipin-shrivastava)
    * Bug #483: Delete all customer ticket attachments from file system when deleting customer account (papnoisanjeev)
    * Bug #482: Delete customer profile picture from file system when updating account details (papnoisanjeev)
    * PR #479: Updated error message in case of issues sending emails through swiftmailer (PeopleInside)
    * PR #478: Render custom fields snippet if any custom field definitions are available (vipin-shrivastava)
    * Bug #477: Display correct channel name when viewing form builder tickets (vipin-shrivastava)
    * Bug #474: Wrong total customer tickets count being showed while viewing ticket (vipin-shrivastava)
    * Bug #473: Fix wrong custom field placeholder value being rendered while creating ticket (vipin-shrivastava)
    * PR #472: Render custom fields snippet only if any custom field details are provided by customer (vipin-shrivastava)
    * PR #468: Fix version number mentioned in dashboard (PeopleInside)
