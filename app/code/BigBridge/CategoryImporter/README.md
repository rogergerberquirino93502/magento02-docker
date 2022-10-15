# CategoryImporter module for Magento 2 (2.1.x)

Simple category importer from CSV file. Allows to import / update 
categories not only from Magento shops (use old shop category ID)

# Features

- adds attribute to category 'Old category ID'
- adds possibility to add custom attribute codes by command option
- adds links between parent and child categories (by 'Old category ID')
- work only for admin store (multi stores are not implemented yet)

# Usage

**NOTICES:** 
- please add parent categories before children in file
- use semicolon (';') as delimiter in file

**Attributes supported by default:**

- Required attributes:
	*	id 
	*	name
	*	parent_id
	
- Optional attributes with predefined values:
	*	is_active - default value: 1
	*	is_anchor - default value: 1
	*	include_in_menu - default value: 1
	*	custom_use_parent_settings - default value: 1
    
- Base additional attributes:
	*	description
	*	meta_title
	*	meta_keywords
	*	meta_description
	*	url_key
	*	url_path
	*	position
	
**Base usage examples**

        bin/magento import:categories [--path|-p <path to file in Magento dir>] [--additional|-a <additional attributes separated by comma>]

		bin/magento import:categories -p var/import/categories-example.csv 
		
        bin/magento import:categories -p var/import/categories-example.csv -a my_custom_attribute1,my_custom_attribute2,my_custom_attribute3
