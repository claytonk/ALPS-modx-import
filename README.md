# ALPS MODX Import

This utility allows the quick transfer of existing content from a MODX site to an ALPS Wordpress.

## Description

This utility allows administrative users who also have an admin account on an existing MODX site to initiate an automated transfer of resource content and linked images/files. Multiple options are provided for selecting and filtering exsting content for import.

## Important!

This utility requires an endpoint be present on the remote site. The resources for that endpoint are not part of this utility. You will need to contact me to set up the endpoint on any site you intend to import. The endpoint requires minor, but very important adjustments from site to site based on the nomenclature of the template variables.

## Installation

Because this is a private repository cloning is problematic so a manual install will be easier. **If Cloudflare is running on the domain you will need to deativate it temporarily to avoid interference during import.**

1. Download repository
2. Upload zipped repository to your wp-content/plugins/ folder
3. Unzip repository, **rename extracted folder as "modx-import"** and delete repository archive
4. Activate the plugin through the 'Plugins' menu in WordPress
5. You should see a "MODX Import" menu item in your admin sidebar. Click it to begin the import process.
