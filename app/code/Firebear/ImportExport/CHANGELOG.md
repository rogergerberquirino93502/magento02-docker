1.5.0
=============
* Fixed bugs:
    * Fixed an issue where strategy validation did not work with value "skip on entries"
* Restructured code for form of Import Jobs:
    * Form at style of Accordeon
* Add features:
    * Add inline edit for field Cron in Grid
    * Add validate of file after entered data for file
* Add Export Jobs:
    * Add grid
    * Add form
    * Add commands
    * Add crontab
    
2.0.0
=============
- general refactoring
- add export jobs similar to import jobs with mapping
- refactoring and improvements for import mapping
- hardcoded values / default values on mapping export 
- Magento 1 pre set for import jobs
- export orders jobs with mapping and filters
- add file validation on import job
- advanced pricing import performance issue
- filtering for export for all entities by attributes
- interaction for default values when should be unique , x1, x2 etc. 
- default / hardcoded value suffix & prefix 
- detailed logging 
- sample files included to extension pack & download from manual 
- unzip / untar file before import 
- upload CSV directly to import job on import form (in web browser)

2.1.0
==============
* Import and Export Fixed Product Tax
* Fix bugs:
   - Hardvalue for field of Entity
   - Load page add Job in Export
   - Import and Export in Admin
   - Correct values for fields of bundle product
   - Check Area Code in Console
   - Delete element in Map
   - Off Logs to Console via Admin
* Add rules for new variations of Configurables Products
* Support Language in console
* Support Language in Cron
* Add Behaviour "Only Update" for Entity of Products
* Add fields for Export's Filter: product_type, attribute_set_code
* Unset special price
* Run re-index command line automatically after import processed
* Import category by Ids and Path of Ids instead of category name
* Generate unique url
* Divide Additional Attributes

2.1.1
==============
* Add Mapping of Categories
* Export Categories
* Load images via HTTP Auth
* Fix bugs:
   - Cannot set zero value for Default Column in Map Attributes table of Import Job
   - Column of Mapping is Empty after load
   - Cannot change Attribute Set
   - Cannot load file via url
   - Cannot minify js files
   - Cannot load image for Configurable product
   - Cannot open page of Export job fast
   - Cannot export bundles and grouped products
   - Cannot add some identicaly fields in mapping
   - Cannot load page of Export job fastly
   - Cannot category url if duplicate
   - Cannot update price and qty
   - Cannot set value for some attributes im mapping
   

2.1.2
==============
* Add presets of Shopify  
* Add price rules feature:
    - Change import price according to price rules
    - Set fixed or percent price margin
* Add Import of Coupons
* Add Import of Cms Pages
* Fix bugs:
   - Cannot create value of attribute from configurable_variations
   - Cannot set values for different stores
   - Cannot import product from Magento1 if empty lines

2.1.3
==============
* Fix core bug if create bundle item
* Fix bugs:
   - Cannot add category in Price rules
   - Duplicates Attributes in Price rules
   - Cannot create categories of different levels
   - Cannot correct import CMS Pages
   - Cannot change delimiter for Categories
   - Cannot create some values of attribute for type of multiselect
   - Cannot changes values of attributes of Categories if different stores

2.1.4
==============
* Fix bugs:
   - Cannot change mapping categories
   - Cannot add new values for attribute
   
2.1.5
==============
* Fix bugs:
   - Cannot import conditions for CartPriceRules
   - Cannot add categories in mapping
   - Cannot add Tier prices
   - Cannot change name of Category for different stores
   - Change CSV for Export Orders
   
2.1.6
==============
Add XSLT For Xml for Import

2.1.7
==============
Add XSLT For Xml for Export

2.1.8
==============
Add Custom field for mapping
Add reset mapping


2.2.0
==============
Add Import of Orders
Add import JSON

2.2.1
==============
* Fix bugs:
   - Cannot load additional images

2.2.2
==============
* Add buttons "Duplicate"

2.2.3
==============
* Fix bugs:
   - Export CMS Blocks and Pages
* Add REST API
* Order export new file format
* Add support ods, xlsx and xls

3.0.0
==============
* Added support of Excel XLSX file format
* Added support of OpenOffice ODS file format
* Added support of REST API  – XML files with XSLT templates and custom Json files
* Added support of SOAP API – XML files with XSLT templates and custom Json files
* Added improved Json file compatibility
* Added new entity Product Attributes – now all attributes, attribute sets and groups can be imported to Magento 2
* Added consecutive export procedure – the export jobs can now remember already exported entities and export only NEW entities added since the last run
* Export date and time can now be added automatically to the file name
* All files from the specified folder can now be imported in a single job
* Swatch attribute values, both color and image, can now be imported along with products
* Default product variations of Improved Configurable Product extension can now be imported
* Added compatibility with the following third-party extensions:
    - MageWorx Advanced Product Options
    - MageStore Inventory Management
    - Wyomind Advanced Inventory
    - MageDelight Price per Customer
* Add presets of Magmi

3.1.0
==============
* Features:
    * Map Attributes – Apply Default Values to – decide if default value should be applied to empty or all rows
    * Attribute value mapping – decide which exact attribute values you want to update, paste them and the new value
    * Root Category – select root category to reference category paths in the import file
    * Round prices and special prices – automatically adjust prices to .99 or .49 whichever is closer
    * Export job event system – whenever the Magento 2 event happens the job is automatically executed
    * Attribute set update – an additional product attribute which defines if the existing product’s attribute set should or should not be updated
    * Configurable product custom logic – copy simple product attributes for configurable – now you can copy selected attribute values of the simple products to configurable product
    * Not remove current mappings for using same mapping for different file upload. 
* Bugfixes:
    * Simple custom options are not imported properly
    * Issue with ‘Category Levels separated by’ setting
    * additional_attributes attribute missing in the attribute mapping column
    * Issue with Only Update behavior importing stock values
    * Issue with text swatch attribute displaying as a dropdown
    * Fixed Product Tax issue with updating existing products
    * Job page loading speed improved
    * Imported configurable products are no longer automatically put in stock after import
    * Configurable products are no longer created if there are no variations or a single variation
    * Issue with importing products with the same URL key creating multiple products
    * Updated links to the sample files inside import jobs
    * Issue with product export missing bundle and downloadable attributes
    * Issue with downloadable product links not being updated on import
    * Issue with filter conditions
    * Issue with checking for existing SKUs in the database
    * Issue with customer composite entity type import
    * Issue with exporting products with ‘Divide Additional Attribute’s option enabled
    * Url key duplicate issue when the file contains several products with the same url_key
    * Undefined index issue during update not existing bundle product
    * The issue with EntityLinkField during product update 
    * Product attributes replace import issue 
    * Link to download sample files in import job issue
    * Price Rules tab issues. When a customer selects a category condition or an attribute which have options

3.1.1
==============
* Features:
    * Changed the default display of section 'Store filter'
* Bugfixes:
    * Catalog product links import issue. When a file contains several rows for the same product
    * Bundle option products import issue. When a file contains several rows for the same product
    * Custom option products import issue. When a file contains one rows with a not null store_view_code value
    * Fix for configurable product when the configurable product is already created and the visibility of newly added simple products are not update.
    * Magento 2.3 compatibility issue(Import Products)
    * Product custom options import issue
    * Product custom options import issue
    * Importing products issue when the php version below 7.1
    * Issue with importing a product that contains an multi-select attribute
    * Incorrect display of fields in 'Map attributes' block
    * The issue with address duplicate during customer address import
    * The issue with default address attributes during customer address import

3.1.2
==============
* Features:
    * Magento 2.3 support added.
* Bugfixes:
    * Fix issue when importing empty attributes 'available_sort_by' and 'default_sort_by' (magento 2.3).
    * Fix issue with duplicated options (magento 2.3).
    * Fix issue when importing orders with empty country_id (magento 2.3).
    * Fix problem with "Clear Attribute Values" option (magento 2.3).
    * Fix import of customers and addresses (magento 2.3).
    * Fix error when replacing products: Invalid value for Attribute Set column (set doesn't exist?) (magento 2.3).
    * Fix error when simple products are not attached to configurable (magento 2.3).
    * Fix compilation error: Incompatible argument type. Magento compiler allow only one parent::__construct() calls.

3.1.3
==============
* Bugfixes:
    * Issue with cron expression is not set
    * Issue when the row does not contain complete information about custom options
    * Custom columns were added to the System Attribute drop-down in ‘Map attributes’ block
    * Remove extra whitespaces from xml import form definition
    * Issue with bundle product attributes: price_type, sku_type, weight_type
    * Added validation of the field “custom_layout_update”
    
3.1.4
==============
* Features:
    * Remove existing categories from imported products and assign only the categories from the imported file
    * Remove existing store views from imported products and assign only the store views from the imported file
    * Import product categories by IDs with categories_id attribute (categories should already exist at the store)
    * Only add import behavior
    * Added checking attribute presence in attribute set, when importing products
* Bugfixes:
    * Added support of increment_id for importing customer addresses, which gives the ability to update existing addresses
    * Added support for query type image URLs
    * Added UrlKey Manager to check existing product URLs
    * Removed extra whitespaces from REST api for JSON options
    * Issue with Magento 2.2.7 History Model defined as private in parent class
    * Issue when the row does not contain complete information about product custom options
    * Issue with mapping same attribute with different system attributes
    * Issue with additional images multivalue separator. Added a condition to check for the previous version
    * Foreign key issue when using ProxySQL
    * Issue with absolute path of хml file (magento 2.1.8)
    * Issue with ‘category’ and ‘product_websites’ attributes for products not exporting when multiple store_views are selected
    * Issue with fresh installation of the extension
    * Issue with importing a single product in several bunches
    * Issue with swatch option update during product import procedure
    * Issue with importing bundle products in Magento 2.1
    * Issue with the stop on error option during the import process
    * Issue with directory separator in the export file path
    * Issue with visibility store filter on export job
    * Issue with undefined product_type and set positionRows for linking related,cross sell,up sell products.
    * Resolved Issue with Category url_key.
    * Issue with export category. When an category has a wrong attribute (magento version < 2.3)
    * Issue with import of customers with less than Magento 2.2.5.
    * Issue with sorting on the history page
    * Issue where a multiple value separator is not used when exporting products
    * Issue with Magento1 file check for illegal string and give error.
    * Added a check of illegal string to Product __saveValidatedBunches
    * Issue with attribute group import. When a default group name is changed
    * Issue with attribute label import
    * Issue with setting value of selection_can_change_qty 1 or 0.
    * Issue with customers export when an attribute mapping is specified
    * Issue with attribute label update
    * Issue due to wrong namespace in di.xml
    * Issue with undefined array index during import product. When import behavior is set to Only Add
    * Issue with undefined array index during product export process
    * Issue with the import of orders when all the "Tax items" are selected (EE/B2B)

3.1.7
==============
* Features:
    * Huge incredible improvements of import product speed: memory overflow was fixed, custom options import was refactored
    * Feature to remove Images for both simple and configurable products.
    * Rework of extension backend menu
    * Rest API option was added to the export source
    * Added input of a range of values for the filter by price. Range added with a hyphen (eg 10-60 or 0-25)
    * Implemented unification of filter values for a fixed price and a percentage discount.
    * Support of remove old categories for config products created on the fly.
    * Added the ability to add related, cross-sells or up-sells products to "Custom logic for creation of configurable products"
    * Solved product export performance issue (only required attributes are selected from collection)
* Bugfixes:
    * Fixed issue when only first custom option was validated while product import
    * Customer address import issue. When a file format is ODS
    * Issue with import xlsx file. When a file contains empty cells for the last column
    * Issue with Allowed Errors Count option. When Validation Strategy is Stop on Error
    * Fixed issue show map fields of advanced pricing
    * Fixed issue show filter fields of advanced pricing
    * Empty user agent parameter issue during export an image from CDN
    * Issue with custom options import. When the Map Attributes feature is used
    * Fixed issue with export filters for cms blocks, cms pages, attributes
    * Fixed issue add validation for incorrect product_type value
    * Resolved potential bugs and issues with the only update remove product website when turned on and also with categories showing fatal error.
    * Issue with bundle type attribute import
    * Issue with undefined index https://github.com/magento/magento2/pull/20916
    * Fixed issue import/export mapping - broken design
    * Issue with advanced pricing export filters section has product attributes
    * Show missing "map attribute" option
    * Show missing form elements when conflicting with other extension.
    * Improve export speed. Avoid load extra data.
    * Added valuesForOptions for all entities. 
    * Issue with category url_key.
    * Resolved issue with catalog_product_relation
    * Issue with category use_default getting removed on category update.
    * Changed the algorithm for generating a request for a sample of data in accordance with the set values of the selected filters.
    * Fix issue with SOAP options not being accept while making the call.
    * Fix issue when validate import file in standard import feature of Magento Core
    * Fixed errors when importing orders.
    * Issue with the save_rewrites_history product attribute saving after import
    * Fix empty values for Virtual Swatch and Text Swatch options
    * Fixed issue with XLSX file having empty rows.
    * Issue with replacing orders during import
    * Fixed a bug in the preparation of the data write request custom product options
    * Improved Remove of Categories instead of Interface class.
    * Resolved filename issue with URL.
    * To display error messages on the export job run on a console.
    * Issue with tier price import. When a tire_prices column has extra spaces
    * JSON parsing to find first array.
    * Apply "Multiple Value Separator" setting in Import Job when import GROUPED product using "associated_skus" column.
    * Improve import speed.
    * Fieldset visibility issue on the import job form.
    * Attribute values mapping issue when API returns an array.
    * Fix typo error in source_types.xml
    * Solved issue with setTimeout in JS
    * Resolved issue with undefined JS index.
    * Scurri Alert about JS.
    * Fixed the problem of incorrect messages in the console when importing (deleting) attributes.
    * Fixed the Root Catalog category issue when importing categories.
    * Fix bug resetting the quantity of goods in stock when updating other parameters
    * Fixed bug the value of the text code in the text_swatch attribute is exported only from the admin column, the remaining stores are not exported.
    * Exclude Magento 2 from platform list.
    * Changed the code for obtaining a collection of products when exporting products
    * Fixed category filter issue on product export
    * Fixed very slow Job form load due to heavy category collection
    * Import products with the same url-key find and delete duplicate url-key
    * Fixed unknown entity model exception
    * Avoid fatal error of url_key duplicate.
    * Trim rowData for removing any whitespaces.
    * Fixed problem cannot add more than one filter for export job
    * Fixed import behavior Delete does not work
    * Fixed issue with export filter.
    * Fixed missing 'The import was successful.' message
    * Fixed typeof verification does not work.
    * Add track to shipment use shipment increment id
    * Add track to shipment use order increment id
    * Add sending email when adding a news tracks
    * Minimizing count fields for import credit memo
    * Fixed issue with swatch option.

3.1.8
==============
* Features:
    * Add url rewrites import export
    * Add widgets import export
    * Add reviews import export
    * Wyomind Advanced inventory export of stock to warehouse and import stock qty for particular warehouse id.
    * Added feature to delete attribute option value
    * Add a feature to translate text using Google Translator for particular store and attribute code.
    * Improve "Custom logic for creation of configurable products" feature: Only show super attributes for "Product attributes for variations" instead of all product attributes
    * We can use **Images File Directory** to import image from url.
    * Added position of product in category import & export.
    * Translator can now use both paid and free api.
    * Implemented Catalog Search Terms Import & Export
    * Disable product which are not in the source file. You can select supplier for that job to disable. Supplier attribute code can be selected from the configuration.
    * Implemented Search Synonyms Import & Export
    * Added Import/Export categories by store view.
    * Swatch attribute import/export was added.
    * XLSX select sheet while import.
    * Remove Upsell and Crosssell products on update.
    * Add new cron group to register import or export cron's in the same group.
    * Import/Export product attributes by specified store
    * Export Only admin storeview for products.
    * Change cron group for dynamic cron handling.

* Bugfixes:
    * Fixed import products error.
    * Fixed an issue with default value and storeValue
    * Fixed image issue with upload of same filename.
    * Fixed attribute options are not imported.
    * Resolve Undefined issue while order export.
    * Fixed product import exception generate_url
    * Platform support for files CSV/XLSX/ODS.
    * Fixed change format curl-request in dropbox.
    * add 2.3 stock item interface
    * avoid delete of default value
    * display console message if image file not found.
    * Fixed unserialize error in job form
    * Add filter to children entities
    * Fixed product import issue with downloadable type via console
    * Fixed error whether name is allowed
    * Fix error: Price rules are not applied for existing products (add categories to price rule's condition)
    * Fix error: Cannot edit job after saving job if XSLT code is too long.
    * Hide unavailable options when choose other entities in Import Job (not catalog_product)
    * Fixed issue when all validation error cleared
    * Fixed advanced pricing export undefined index issue
    * Fixed the export customer address issue when _website attribute has an incorrect value
    * Fixed wyomind integration class definition
    * Changed validation errors when importing attributes
    * Longblob column type definition issue was fixed
    * Display update_attribute_set to system mapping list.
    * Fixed issue with pipe separator for varchar attribute changes to comma separated
    * Fix issue with on the fly config product with only 1 variation.
    * Fixed error run export jobs 
    * Fix issue with Textswatch import, if there is change in description it adds new value.
    * Fix export of text swatch to export admin value instead of admin description.
    * Fixed issue with categories import. When an import file has a non default level separator.
    * Issue with uk date format when order import.
    * Fix attribute import with value 0
    * Fix store filter section is displayed on all entities.
    * Fix our sample import file doens't work for import for XML.
    * Fixed issue with pipe separator for text type attribute changes to comma separated.
    * Fix issue with Export Job: cannot set default value to zero
    * Fix tier price is not importing correctly.
    * Fixed the undefined data provider js issue on the import and export job pages.
    * Fixed the issue with the empty dropdown Import Source on the import job page.
    * Fix issue with Export: Remove "additional_attributes" column when turn on "Divide Additional Attributes" option
    * If configurable product not created already. undefined variable found.
    * Fix issue with payment import from Magento1 convert serialize data to json.
    * Fix import product - grouped product - simple products are not attached
    * Fix import product(replace) - console error.
    * Fix import product - console message "url is not unique."
    * Fix with SOAP api validation.
    * Fix an issue with the export of product attached attribute code, even if the value is not assigned. It will export with the default value from the export job form. 
    * Parse additional_attribtues for swatches.
    * Fixed export advanced pricing - not all prices are exported.
    * Refactoring export config, merged config from config.xml and di_export.xml.
    * Added implement EntityInterface into entity export models.
    * Allow translator to send html tags.
    * Allow export of orders if in from-to if to is not specified.
    * Implemented retry functionality when deadlock mysql error are occurring
    * MOM compatible.
    * Fixed export empty fields (custom_options product_websites categories categories_position) if Store Filter is selected.
    * **setFinishedAt** function to be called after the process is complete.
    * Fix bug export products using the export and mapping feature then base_image and product_online or status is not exported when mapping is enabled.
    * Fix problem with line break support when exporting text areas.
    * Create Config product for Magmi products.
    * Fix export filter by datetime attributes.
    * Fixed deleting wrong row issue on attribute mapping tab.
    * If sku is empty show a mistake in console for import job.
    * Fixed mapping attribute type issue on the export job form
    * Product status not exported when select only sku and product_online.
    * Fixed Url Rewrite export: delete metadata filter. Added yes/no select for is_autogenerated filter. 
    * Long import of products issue was fixed
    * productEntityJoinField issue when products export in Magento EE.
    * Fix import category(replace). Behavior replace don't must nothing to add, only replace existing entities
    * Fix import customer(replace). Behavior replace don't must nothing to add, only replace existing customers.
    * Export products page by page to CSV file was added to solve the timeout issue.
    * Fix issue of Extension use Default Category Level Separator.
    * Improve export map fields for order.
    * Filter JS added validation.
    * JSON convert issue on migration.
    * Multi website value replace on admin store view every time even if imported to specific store view separately.
    * Review system_attribute drop load fix.
    * **custom_options** system attribute in Attribute mappings.
    * Retrieve Attribute Id issue was fixed. When attribute code contains point symbol.
    * Attribute options duplicate issue was fixed
    * Fix error during setup:upgrade when making upgrade from importexportfree module
    * Import cart price rules - no cart price rules after import
    * Import cms blocks/pages - ods - not imported by import source Url and not validate
    * Box spout error when not installed.

3.2.0
==============
* Features:
    * Timestamp added to each log entry.
    * Add shipment auto generator.
    * Add invoice auto generator.
    * Add creditmemo auto generator.
* Bugfixes:
    * Fixed all filters for export review.
    * Fixed Search Terms export: delete Is Active, Is Processed filters. Fixed Display in Teams, Num Result filters.
    * Fix Map Attributes Default value for all rows and empty rows.
    * Added custom Search terms mapping.
    * Added custom Search synonyms mapping.
    * Fix Form break when category id not found.
    * Fixed import remote images issue.
    * Fixed import attributes import issue to General group.
    * Fix replace import category.
    * Fix import review(replace). Behavior replace don't must nothing to add, only replace existing reviews.
    * Added Remove all address association checkbox for Customer addresses import. Firstly remove all addresses when customer addresses import.
    * Fix import product. Fix error for magento 2.3.2-p1.
    * Integration for import and export refactor.
    * Export folders are not created on some system.
    * Fix import product. Fix error for magento 2.3.2-p1.
    * Fix issue with widget export.
    * Improvement in select Root Category.
    * Move configurableProducts custom logic to function based.
    * Extension details link was removed from the firebear menu.
    * Remove the images from the server. (To free up space).
    * Fixed permission denied for export if folder is new creating.
    * Fix issue with category position for configurable product when using custom logic.
    * Fix downloadable products import.
    * Fixed sftp creating a new folder if the folder not exist already.
    * Fixed Search terms import if mapping default value is empty.
    * Fix ftp export recursive creates a directory.
    * Fix cart price rules when edit job.
    * Defined variable for uploader.
    * Fix View History - when open two or more job tabs.
    * Occur duplicates for dropdown attributes.
    * Fixed Search synonyms import if mapping default value is empty.
    * Fix issue with HTTPS type image url.
    * Fixed Error on import build address information based on customer information if missing.
    * Fix if isset category_position now will be correctly adding products in category for import products.

3.2.1
==============
* Bugfixes:
    * php7.3.11 breaks on using continue in switch statement

3.2.2
==============
* Features:
    * Add catalog rule import export.
* Bugfixes:
    * Improvements to excel file to fetch sheet through other sources.
    * Export category store_name if changed.
    * Change the format of import and export widgets.
    * Correct saving attribute_set_id for eav_attribute_group table.
    * Fix issue of category name not export store view level.
    * Fixed file path extinction for import job.
    * URL key gets cleared on only update.
    * Issue with configurable product with values like 0 or 00.

3.2.3
==============
* Features:
    * Upload images for swatches through Attribute Entity.
    * Resize Image after Import.
    * Import and export Youtube/Vimeo video.
* Bugfixes:
    * Resolved issue with JS showing feature on wrong entity select.
    * Improve migration add-on functionality
    * Fix Gid Break on Export page.
    * Resolve compilation issue with Magento2.2.x.
    * media path issue if / is not give behind the filename.
    * Entity Id fix.
    * Resolve an issue with create configurableProduct and image copy.
    * Fixed empty import source issue. When any platform is used for import.
    * Fixed issue with decode transaction additional_information in json format.
    * Numeric validation for attribute code was added to attribute import.

3.2.4
==============
* Bugfixes:
    * Fixed a number of items was different after re-exporting.
    * Improvement to URL key generate feature.
    * Fixed a download export file issue. When selected Rest Api export source.
    * Improve Image upload and instead of repetitive upload.
    * Fixed Dynamic price for bundle product.
    * Added attribute set and 'group:name' filter for export attribute.
    * Dynamic rows item delete issue was fixed.
    * Added url key generated for category.
    * Added if store view code field is empty store code is admin.
    * Validation fix for custom URL rewrite entry.
    * Added correct reset data for filter.
    * Added filter by store id for export attributes.
    * Fixed generate url key for category if another entity has the same url key.

3.3.0
==============
* Features:
    * Add newsletter subscriber import export.
    * Added email notifications upon starting and completing import/export jobs.
    * migration add-on: add enterprise url rewrite migration job, delete with conditions preJob
    * migration add-on: add attribute sets and groups migration
    * migration add-on: updated progress bar information in console and added test bunch size option for testing migration
    * Add google sheets export source
    * Google Drive source type was added.
* Bugfixes:
    * Fixed issue with deleting mapping attribute values
    * Row scope when equal to default Store view code override admin value.
    * Generate url key for categories if url key and url path is empty.
    * Fixed filter of export order.
    * Fix opening job if password was changed.
    * Added validation of required fields when importing catalog price rules
    * Added rule_id field in mapping in the 'catalog price' behavior job
    * Fixed New Theme adding for import cms page.
    * Filter type field for export catalog rule.
    * Added check for finding duplicate url key for import category if  behavior replace.
    * Fixed problem when importing orders when replacement behavior is selected
    * Added store view code filter for export block.
    * Fixed undefined variable.
    * Fixed store view code filter of export block for CE.

3.3.1
==============
* Features:
    * Added support of limitation of count of entities for product export.
    * Refactor save action to enable plugins added to controller
    * Make some method public in Model\Export\Product.php to attach plugins

* Bugfixes:
    * Fixed product categories empty value during export

3.3.2
==============
* Features:
    * Add customer groups ids validator
    * Add support for JSON export.
    * Migration add-on: add new mapper for attribute option and job for url rewrites from M2 to M2 migration
    * migration add-on: add duplication cleaner, create company post job, updated sequence tables support, added prefix support for pre jobs

* Bugfixes:
    * Fixed undefined variable. 
    * sql column count error
    * Fixed undefined variable warning
    * Added console error if deleting customer when he is admin company for import customer of behavior delete.
    * Removed protected constants as those aren't allowed in PHP 7.0
    * Dateformat issue when saved for special_price_from_date saved wrong.
    * Fixed export xlsx ods for ftp.
    * Added store view code filter for export page.
    * Fixed store view code filter of export page for CE.
    * Validate customer address entity if same entity id is at another customer. Error output to the console.
    * Fix missing data for column 'category_ids' when export products with mapping and added column 'category_ids' when export products without mapping.
    * Fixed import product for various store view code.
    * Fix behavior replace for quote.
    * Prepare entity name for console output.
    * Fixed behavior replace for quote.
    * Remove temp files after export.
    * Fix id for search terms.
    * Added behavior replace and fixed mapping for search synonyms import.
    * Fixed export advanced pricing with mappings.
    * Fix reviews export remove extra data.
    * Deleted entity Stock Sources for master if module ImportExportMsi don't setup.
    * Fix widget export remove extra data.
    * Fixed behavior replace search terms.
    * Correct saving date for review import.
    * Add skus field validation.
    * Move email notifications section.

3.4.0
==============
* Features:
    * Added the ability to transfer image import to the product queue.
    * Added product cache for import.
    * Import Speed Optimization.
    * Multi-store category content supported and import category with category id possible
    * Improve Platform Logic to take maxDataSize and maxBunchSize.
* Bugfixes:
    * Issue with Attribute imports when the behaviour is append.
    * Fixed order import mapping.
    * Fixed import logging when Email Notifications service is enabled.
    * Improvement to uploader class for image.
    * Exort of attribute_code for multiple attributeSets.
    * Fix issue with customer_composite import.
    * Fixed incorrect product URL key generation when the only update mode was selected and url_key attribute was empty.
    * Fixed the incorrect order date and totals update. When the file contains not a full attributes list.
    * Behavior replace for cart price rule.
    * Added validate attribute_code for import attributes.
    * Fix export category invalid argument.
    * Correct rating saving.
    * Fix configurable product replace if is custom logic for creation of configurable products.
    * Fix display bundle product on frontend.

3.4.1
==============
* Bugfixes:
    * Fixed the issue with module dependence when Dotdigitalgroup_Email is not installed

3.4.2
==============
* Bugfixes:
    * Fixed the issue with module Dotdigitalgroup_Email for Magento 2.3.3.
    * Fixed the issue with logging errors for image import.

3.4.5
==============
* Features:
    * Add product fields to order export.
    * Control import bunch size from the system config.
    * Export of tier_prices during product export.
* Bugfixes:
    * The Stripslashes Php function was added to prepare product data.
    * Fixed the only admin filter issue during the export categories.
    * Fixed behavior replace search terms.
    * Added tooltip for import export job form.
    * Added checkbox add product QTY to existing value for import product.
    * Fixed issue with platform select and using default import source, then other than file is selected it breaks the form.
    * Assign a unique image position issue was fixed.
    * Fix resize images after import if version ee.
    * Fixed Class MediaVideoGallery Dependency for 2.2.3
    * Support for old email generation methods in EmailTransportBuilder and MailTransportBuilder for 2.2.3
    * Support for old version Uploader for 2.2.3
    * Fixed the deffered image error log issue.
    * Fixed the custom options import issue.
    * Fix duplicate attribute options values.
    * Fixed the import url rewrites issue.
    * Fixed the import default value issue when importing products for multiple store views.
    * Fixed the import validation issue when the category url_path is empty.
    * Fixed the undefined category id issue when exporting products.
    * Fixed the database prefix issue when importing products.
    * Use default value for "enable product" in store views
    * Fixed the category export when 'all store views' is set for Magento 2.4.0-beta1.
    * Fixed export attribute.
    * Issue with Magento_Inventory disable for customers then it breaks the import.
    * Hotfix/export mappings improvements
    * The output is not needed in processor
    * Product URL_Key generation
    * Export of attribute_code for multiple AttributeSet
    * url_key generation issue 
    * Export Product Entity and Order Entity in single csv file

3.4.6
==============
* Features:
    * Configurable variation update
    * Add the function to reindex only required indexer after the import process.
* Bugfixes:
    * The validation errors output issue was fixed.
    * The shipment track generation issue was fixed.
    * Json validator issue was fixed.
    * Add swatch value attribute for mapping
    * Product import process lead to 500 internal server error that is fixed with this change.
    * Export of tier_prices during product export.
    * DotMailer fix for M2.3.3-p1.
    * User can change the XSLT, even if wrong XSLT attached.
    * Fixed the issue when extra fields added to file during order export and the enabled option Only fields from mapping.
    * Product option values have been added to the export order file
    * Fix Number of columns does not correspond to the number of rows in the header for ODS.
    * Category url update issue fixed
    * Custom options to be removed with Magentos default `__EMPTY__VALUE__` constant.
    * Export of products on googleSheet have limitation if export more than 500

3.5.0
==============
* Features:
    * Added import/export import-export jobs
    * Product URL generation by pattern was added to import.
    * Added hide characters in the password fields.
* Bugfixes:
    * Fixed the undefined tooltip issue on the export job page.
    * Fixed the incorrect sorting for the attributes of the order entity. When only fields from the mapping feature are selected on the export job page.
    * Fixed an issue with product import of attribute when switched from dropdown to textswatch.
    * The array to string conversion issue was fixed. When the widgets entity was selected.
    * Fixed the import credit memo issue.
    * Fixed the tier price update issue. When the percentage_value attribute was updated.
    * Price Rules fix undefined issue creating extra rows.
    * Product images update issue was fixed.
    * Disallow XML file format issue was fixed.
    * out-of-stock-qty attribute was added to the import product mapping tab.
    * Category Entity validateRow, with required attributes.
    * Undefined index issue on categories mapping was fixed.
    * Add Log message to console to show image is downloaded from sources.
    * Url key update at store view level issue was fixed.
    * The error validation issue was fixed. When need to specify allowed errors count for the validation strategy.
    * Fixed the incorrect export file structure issue. When the file format is the XML or JSON, the entity is a sales order.
    * Improvements to XSLT Translation.
    * Changed the list of allowed file extensions to specify which export file formats are available for download.
    * Fixed error while creating object for Magento\MediaGalleryIntegration\Plugin\SaveImageInformation for Magento 2.4.x

3.5.1
==============
* Features:
    * Pagination was added to the import/export mapping grid.
    * The find and replace function was added to the import job.
    * Added API endpoints for import job processing.
    * Added feature to archive a file to import and export jobs.
    * Added hide characters in the username fields.
    * msi_source_code attribute can be added during product import (M2.3 and above).
    * Product option id attribute was added to import/export.
* Bugfixes:
    * Duplicate upload of the import source file was removed.
    * Don't disable the products which are cached.
    * The category import issue was fixed: URL key is not set by Default.
    * The fileformat classes to be able to load from di or ObjectManager.
    * Export job check job is enabled during event based export.
    * Fixed import category name when reimport export file.
    * Webkul Marketplace product import issue was fixed.
    * Init error templates to show correctly on validation.
    * Advanced pricing models constructor issue was fixed for Magento 2.4.2 version.

3.5.2
==============
* Features:
    * Configurable products import speed improved.
    * Multi-source warehouse reference and warehouse quantity attributes added into the products import and export.
    * Added an additional ability to generate a url key for products by the attribute color and the use of some php functions of pattern.
    * Delete file after import.
    * Tab tabulation was added as a supported separator for Field Separator and Multiple Value Separator import/export job options.
    * Added support to import file of supported formats which was added to the zip archive.
    * Scan and import files from folder/FTP/SFTP.
* Bugfixes:
    * Issue with product images import was fixed. When Remove All Images and Remove Images from Directory options are enabled.
    * Fixed the skip errors validation strategy issue when importing entities from a console.
    * Issue with the import product images from Google Drive and Dropbox was fixed.
    * Fixed the issue with the product price rules import by attribute set conditions.
    * Fixed do not save the address of the link if the link is 404.
    * Fixed the product's default stock data update issue. When the file doesn't contain the stock data attributes.
    * Fixed the issue with export products page by page.
    * Fixed render image if path not url.
    * Fixed the issue with the import process. When the file type is JSON.
    * Fixed the category update issue.
    * Duplicate Export job behavior was adjusted to consider unique file paths.
    * Find & replace block was hidden. It will be visible when the import file is validated.
    * The export product issue was fixed. When added filter for the multi-select attribute.
    * Customer's confirmation import issue was fixed.
    * Fixed the category import issue. When the wrong position value specified in the file.
    * Fixed the replace behavior for advanced price entity.
    * Fixed the issue with the category mapping tab when several pages are added.
    * Fixed the image import issue through URL.
    * Fixed generate unique url if duplicate issue.
    * Fixed export Advanced Pricing issue. Tier price is fetched by entity link field.
    * Fix parameter if import from terminal.
    * Fixed duplicate image each entry in each store.
    * Fixed the issue with the categories mapping when changing the import source.
    * Fixed the issue with the import via sftp and ftp.
    * Fixed the issue with the import from google sheets.
    * Fix the issue with updating the 'is anchor' column.
    * Fixed the filter from tier price.
    * Fix export filter errors.
    * Added url key validator.

3.6.0
==============
* Features:
    * Added Run jobs one by one.
    * Added "Associate child reviews to parent" future.
    * Upgraded Box/Spout library version to ^3.1.
    * Added possibility to add tracking number without skus and complete order.
    * Added possibility to import only fields from attribute mapping - Product entity.
    * Added possibility to import only fields from attribute mapping - Order entity.
    * Added possibility to import only fields from attribute mapping - Category entity.
    * Added possibility to import only fields from attribute mapping - Customer entity.
    * Added possibility to import only fields from attribute mapping - Customer Address entity, Customers and Addresses (single file).
    * Added possibility to import all files from the folder.
    * Added possibility to run jobs one by one.
* Bugfixes:
    * Fixed an issue with duplicate images in the db, when importing products for all store views.
    * Fixed an issue with incorrect updating of custom options when importing products.
    * Fixed an issue with "Clear Attribute Values" with only update behavior.
    * Fixed an issue with missing downloadable information for a downloadable product in the export file.
    * Fixed "Item with the same ID already exists" error after importing a downloadable product.
    * Fixed issue with products when import not working when both options are enabled (Remove All Images and Remove Images from Directory).
    * Fixed issue with products when import with specific separators has wrong import result.
    * Fixed issue with products downloadable type when group_title value isn't imported.
    * Fixed issue when email send without error log and before process complete.
    * Fixed issue with export products when base_image, base_image_label, thumbnail_image, thumbnail_image_label values aren't exported with 'Only field from mapping : Yes'
    * Fixed issue with import products when use_config_manage_stock isn't updated to Yes.
    * Fixed issue with error Undefined index: copy_simple_value.
    * Fixed issue with products when import the file with 'image' attributes, but without 'store_view_code' attribute.
    * Fixed issue when 'Delete file after import' doesn't work.
    * Fixed issue with validation strategies 'Skip error entries/Stop on Error' when an import job runs by CRON.
    * Fixed issue with import products when products are not deleted without the product_type attribute.
    * Fixed issue with import orders when 'Integrity constraint violation' error occurs.

3.6.1
==============
* Bugfixes:
    * Fixed serializer for compatibility with Magento 2.4.3

3.6.2
==============
* Bugfixes:
    * Removed Laminas library dependency.

3.7.0
==============
* Features:
    * Improved a filter by categories for a product export job.
    * Improved optimization.
    * Added the ability to store logs in the database.
* Bugfixes:
    * Fixed an issue with the "Map Attributes" mapping validation.
    * Fixed issue with validation strategy when importing a file with less rows than the maximum number of allowed errors.
    * Raised the version of "salsify/json-streaming-parser" module (~6.0 => ~8.0).
    * Fixed an issue with wrong order of options after importing attributes.
    * Fixed issue with import iages with custom roles.
    * Fixed an issue with updating the status and visibility of configurable products when using the 'Custom logic for creation of configurable products' setting.
    * Fixed issue with 'Disable products which are not in the file.' functionality when using the magmi platform.
    * Fixed OneDrive permission problem and root slash.
    * Fixed issue with the parallel launch of jobs.
    * Fixed issue with export quantity filter.
    * Fixed issue with importing orders when the order isn't correctly displayed on the dashboard.
    * Fixed issue with json serializer with Malformed UTF-8 characters error.
    * Fixed issues with CmsPage and CmsBlock export job filters.
    * Fixed issue with a product filter by stock status when exporting products.
    * Fixed issue with import product category errors.
    * Fixed issue with updating the Salable Quantity.
    * Fixed issue with import configurable and simple products when using a magento 1 platform.

3.7.1
==============
* Bugfixes:
    * Fixed issue with extra attributes in a configurable product variations

3.7.2
==============
* Bugfixes:
    * Fixed issue with regeneration of url-keys by name attribute when updating products with 'sku' and 'name' columns.
    * Fixed issue with copying category ids from a simple product to a configurable product when 'Custom logic for creation of configurable products' is enabled.
    * Fixed issue with incorrect creation of configurable variations after each bunch, when a configurable product and its variation in different bunches.
    * Fixed issue with updating  stock status.
    * Fixed issue with empty columns in an export file after exporting products with the 'Only fields from Mapping' option enabled.
    * Added resizing of images to the image import process queue.
    * Fixed issue with updating labels for images.
    * Fixed issue with order filtering by Sales Order Products.
    * Added downloadable product link data for importing orders and exporting orders.
    * Fixed issue with incorrect tier prices export when exporting products.
    * Fixed "Test XSL Template" error when XML file as URL is used and XSLT configuration is enabled.
    * Fixed issue when using php function in XSL (registerPHPFunctions).
    * Fixed issue with incorrect copying of an attribute values when using the "Custom logic for creation of configurable products" behavior.
    * Fixed issue with products export where an image becomes hidden for all store views instead of a specific store view.
    * Fixed issue with custom logic 'Product attributes to copy value’ for relates_skus.
    * Fixed issue with the YouTube video import.
    * Fixed issue with importing url rewrite for cms-pages and products.
    * Fixed issue with creating new columns in the DB to store logs when upgrading to a new version of the extension.
    * Fixed issue with running import jobs in a chain.
    * Fixed issue with an attribute replace.
    * Fixed issue with getting one drive redirect uri.
    * Fixed issue with deletion behavior in an url rewrite import job.
    * Fixed issue with configurable product not displaying on frontend after creating it via import.

3.8.0
==============
* Features:
  * Changing setup scripts to a declarative scheme.
  * 'Import base image as small and thumbnail' setting was added to the import product job.
  * Additional images and additional image labels fields were added to the product attributes list for copy values
  * Added row split by store_view_code during product import.
* Bugfixes:
  * Fixed issue with the 'Custom logic for creation of configurable products' functionality when using custom rules.
  * Product name field was added to export reviews.
  * Fixed issue with importing an order with multiple shipments.
  * Fixed issue with 'Export page by page' functionality.
  * Fixed issue with url when importing multiple rows for the same product.
  * Fixed issue with adding advanced pricing during product import.
  * Fixed issue with the re-import of images that were deleted from the media directory.
  * Fixed issue with extra import file types when the 'Use API' is enabled.
  * Fixed issue with creating custom options when exporting products.
  * Fixed issue with incorrect category image name after importing categories.
  * Fixed issue with not added associated products to the group product if the import table has specific store view.
  * Fixed issue with products caching functionality when using platforms during import.
  * Fixed issue with an empty category field in a configurable product created using custom logic.
  * Added an 'overflow' css property to categories mapping fields.
  * Fixed issue with importing products when the catalog price scope is a website.
  * Fixed issue with saving category url key and category url path.
  * Fixed issue with an empty store_name field in the categories export file.
  * Fixed issue with replacing advanced pricing.
  * Fixed issue with different number of row customizers when running export jobs from different scopes.
  * Fixed issue with archiving import file after importing from remote sources.
  * Fixed issue with importing product with 'only update' behavior, when a 'Undefined index:sku' exception occurs.
  * Fixed issue with endless paginated export of products.
  * Fixed issue with "Map Categories" mapping.
  * Fixed issue with importing categories from a xlsx file.
  * Fixed issue with URL key validation when importing categories.
  * Fixed issue with import products with same url keys when they are on different stores.
  * Fixed 'Passing null to argument of type string is deprecated' issue (compatibility with php version 8.1 and Magento version 2.4.4)
  * Fixed issue with nesting level of tags in templates (compatibility with Magento version 2.4.4)
  * Replaced some calls to deprecated js functions with calls to recommended functions (compatibility with JQuery version 3.3.2 and Magento version 2.4.4)
  * Fixed issue with importing new product images.
  * Fixed issue with mappings on the product import/export edit page (compatibility with Magento version 2.4.4)
  * Fixed issue with export when source is REST API and file format is ODS, XLSX or JSON.
  * Fixed issue where patches were not being applied
  * Fixed issue with incorrect update of product url key after product import
  * Fixed issue with import via sFTP (compatibility with Magento version 2.4.4)
  * Fixed issue with copying multiple additional images from simple product to configurable when creating a configurable product on the fly.
  * Fixed issue with running 'queue:consumers' commands (Compatibility with magento 2.4.4 and php8.1)
  * Fixed issue with product export (Compatibility with magento 2.4.4 and php8.1)
  * Fixed issue with data export to One Drive (Compatibility with magento 2.4.4 and php8.1).
  * Fixed issue with data export to google sheet.
  * Fixed issue with an order view page after order import.
  * Replaced the 'box/spout' library with the 'openspout/openspout' library (Compatibility with magento 2.4.4 and php8.1).
  * Fixed issue with a product import when 'Round Special Price to 0.49 or 0.99' setting is enabled (Compatibility with M2.4.4 and php8.1).
  * Fixed issue with importing images.
  * Fixed issue with order export (Compatibility with magento 2.4.4 and php8.1).
