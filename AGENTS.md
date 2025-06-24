# CS-Cart Add-on Development and Architecture Guide

## Platform Overview and Architecture

CS-Cart is a PHP-based e-commerce platform (supporting PHP 8+) with a modular architecture designed for extensibility. It uses MySQL (with a default table prefix) for data storage and Smarty templating for the front-end. The platform is offered in editions like **Ultimate** (single-store) and **Multi-Vendor** (marketplace), but the core architecture remains consistent across editions. Key components include an **admin panel** (backend) for store management and a **storefront** (frontend) for customers, each with separate controllers and templates. The system follows a functional MVC-like pattern: **controllers** handle requests and prepare data, **Smarty templates** render the UI, and **global functions/classes** act as the model/business layer.

**File System Structure:** CS-Cart’s codebase is organized into specific directories each serving a distinct purpose. Below is a high-level overview of the CS-Cart folder structure, highlighting important directories and their roles:

```plaintext
root/
├── app/                    # Core application code
│   ├── addons/             # Add-ons (extensions/modules) go here
│   ├── controllers/        # Controllers: request handlers (backend, frontend, common)
│   ├── functions/          # Core functions (fn_*.php files grouped by domain)
│   ├── lib/                # Third-party PHP libraries
│   ├── payments/           # Payment gateway scripts
│   ├── schemas/            # Configuration schemas (menus, block definitions, etc.)
│   └── Tygh/               # Core PHP classes (namespace Tygh\*)
├── design/
│   ├── backend/            # Admin panel design (templates, CSS, images for backend)
│   └── themes/             # Storefront themes (templates, CSS, images for frontend)
├── js/                     # JavaScript libraries and add-on scripts
├── var/
│   ├── cache/              # Cache files
│   ├── files/              # Export/import and uploaded files
│   ├── languages/          # Translation files (.po language packs)
│   └── themes_repository/  # Base themes and templates for installation
├── images/                 # Image files (thumbnails, etc.)
├── admin.php               # Entry script for Admin panel (backend)
├── index.php               # Entry script for Storefront (frontend)
├── api.php                 # Entry script for API (if using REST API)
├── config.php              # Core configuration (general settings)
├── config.local.php        # Local configuration (environment settings)
└── init.php                # Application initialization script
```

In this structure, **`app/`** contains the back-end logic: built-in **controllers** (divided into `backend/`, `frontend/`, `common/`), **core functions** (in `functions/` \*.php files), **libraries** (third-party code in `lib/`), **payment integrations** (`payments/`), **schemas** (pre-defined arrays for menus, settings, etc.), and **core classes** under the `Tygh` namespace. The **`design/`** directory holds all Smarty templates and design assets, separated into `backend/` for admin panel and `themes/` for storefront themes (customer-facing). Each theme has its own folder under `design/themes/`, containing templates, CSS/LESS, and media. The `var/` directory stores runtime-generated files (cache, etc.) and also a `themes_repository` for original theme files and email templates.

**Multi-Store and Multi-Vendor:** CS-Cart supports multiple storefronts and vendor marketplaces. The codebase is largely shared; differences are handled via conditions and add-ons. The structure above remains the same, but for Multi-Vendor, additional data (like vendors, vendor-specific settings) is managed through add-ons and core functions. The **multiple storefront** capability is built-in (see `config.php` and settings), ensuring a single codebase can serve multiple domains or storefronts. Developers should be mindful of edition checks (e.g., some features enabled only in Multi-Vendor) often indicated by conditions in code or addon XML (using `edition_type` attributes).

**Core Architecture Pattern:** The platform uses a combination of functional and modular patterns:

* **Controllers** (essentially page controllers) are plain PHP scripts that get included based on the `dispatch` parameter of the request.
* **Global functions** (prefixed with `fn_`) in `app/functions/` encapsulate business logic (e.g., `fn_cart.php` handles cart operations, `fn.catalog.php` handles product catalog logic).
* **Hooks** (events) and **add-ons** provide an inversion-of-control mechanism to modify or extend behavior without altering core files.
* The **Tygh Application container** (`Tygh::$app`) is an Inversion of Control (IoC) container introduced in CS-Cart 4.3.x, which manages services (singletons/factories for things like database, template engine, mailer, etc.). For example, `Tygh::$app['view']` gives the Smarty templating engine instance. The container uses Pimple under the hood, enabling **lazy loading** of services and dependency injection. This modernizes the architecture while maintaining backward compatibility (legacy `Registry` calls are forwarded to the container for certain keys like `class_loader`, `view`, etc.).
* **Smarty Template Engine:** CS-Cart uses Smarty for rendering both the storefront and admin panel UI. Templates (`*.tpl` files) are organized by feature and view under `design/…/templates/`. Templates often contain hook placeholders (described below) to allow add-ons to insert content. Front-end assets (CSS, JavaScript) live in the theme or in `js/addons/` and `design/backend/css/addons/` for admin styles.

**Development Approach:** CS-Cart is designed to be **extended via add-ons instead of modifying core code**. The **extension mechanisms** (hooks, add-on schemas, controllers, etc.) allow adding new features or altering behaviors in a **upgrade-safe** manner. In the following sections, we detail the structure of add-ons and how to utilize hooks, controllers, and other patterns to build new functionality for CS-Cart.

## Standard Add-on File Structure

Add-ons (also called **modules** or extensions) are self-contained packages that extend CS-Cart’s functionality. An add-on’s files reside in a dedicated subdirectory under `app/addons/`. The folder name is the **add-on identifier**, which should be unique and is used in code and in the add-on’s manifest. CS-Cart will detect any folder in `app/addons/` as a potential add-on.

**Required Files and Directories:** Every add-on **must** include the following in its main directory (`app/addons/<addon_id>/`):

* **`addon.xml`** – The add-on’s manifest file (XML format) describing metadata and install/uninstall scripts. This file is required to register the add-on in CS-Cart.
* **`func.php`** – PHP file for add-on-specific functions. This is where you implement any custom functions (including hook handlers) used by your add-on’s logic. It’s loaded when needed (e.g., when hooks execute or when the add-on’s functions are invoked).
* **`init.php`** – Initialization script for the add-on. This file is executed on every page load *if the add-on is active*. Its primary purpose is to register hooks and perform early setup like defining constants or including PHP files. (For example, `init.php` typically contains calls to `fn_register_hooks()` for PHP hooks that the add-on implements.)
* **`config.php`** – Configuration settings for the add-on (if needed). This file can define add-on specific configuration variables. (Not all add-ons have a config.php, but it can be used for defining static config or including required files.)
* **`controllers/`** – Directory for controller files. This can have `backend/` and/or `frontend/` subfolders, containing PHP controller scripts that either **add new pages** (unique controllers for this add-on) or **extend existing pages** via pre/post controllers. For example, `controllers/backend/my_feature.php` would define a new admin page for the add-on, whereas `controllers/frontend/products.post.php` could extend the core products page.
* **`schemas/`** – Directory for schema definitions that extend core schemas. Schemas are PHP arrays used by CS-Cart for various configurations (e.g., menu structure, permissions, block definitions). An add-on can provide `*.post.php` schema files to merge its data into core schemas. Common subdirectories include:

  * `schemas/menu/` – to add new menu items in the admin panel navigation.
  * `schemas/permissions/` – to define permissions for new controllers.
  * `schemas/block_manager/` – to define new block types or block templates.
  * `schemas/settings/` – to define settings if using older scheme (though in scheme 3.0 settings are in XML).

All files except templates should reside in the `app/addons/<addon_id>` directory. CS-Cart loads these according to context (e.g., controllers on dispatch, init.php on init, etc.).

**Optional Directories for Templates and Design:** Add-ons often need to provide UI components (Smarty templates, CSS, JS) for both the storefront and admin panel. Instead of placing these in the core design folders, add-ons use special directories to integrate their templates with the platform:

* **Storefront Templates:** Place customer-facing templates under `design/themes/<theme_name>/templates/addons/<addon_id>/`. Typically, you provide templates in the **“base”** responsive theme directory (e.g., `var/themes_repository/responsive/templates/addons/<addon_id>/`) which CS-Cart will copy to active themes on installation. Under this, you can mirror the normal template structure (e.g., `views/` for new pages, `hooks/` for hook templates, `blocks/` for new block templates, etc.).
* **Admin Panel Templates:** Place admin-facing templates under `design/backend/templates/addons/<addon_id>/`. For example, if your add-on adds a new admin page or modifies an existing one via template hooks, you’ll put those templates here (e.g., `design/backend/templates/addons/<addon_id>/views/my_feature/manage.tpl` for a new page, or `hooks/` subdirectory for hook insertions).
* **Email Templates:** If your add-on sends custom email notifications, you can include email templates under `var/themes_repository/responsive/mail/templates/addons/<addon_id>/`. These will be copied to the live mail templates directory on install, similar to storefront templates.
* **Assets (CSS/JS):** Add-on specific CSS/LESS or JS can be placed under the theme’s `css/addons/<addon_id>/` or `js/addons/<addon_id>/` directories respectively. For admin panel styles, use `design/backend/css/addons/<addon_id>/`. CS-Cart will include these assets when the add-on is active. For example, a file `design/backend/css/addons/my_addon/styles.less` will be compiled and included in the admin panel if present.

*Hint:* The add-on’s directory structure should mirror the core structure for any elements it extends or adds. For instance, to override a core template or add a language file, use the same path under your add-on’s folders. This consistency ensures the platform can locate and merge the add-on’s files appropriately. You are free to create additional subdirectories (e.g., `lib/` for libraries, `Tygh/` for classes – discussed later) as needed, but maintain naming consistency with CS-Cart’s conventions for smooth integration.

By following this standard structure, you ensure that CS-Cart can recognize and integrate your add-on. In summary: *all PHP logic for add-ons stays in `app/addons/<name>` while all templates and front-end assets go into the corresponding `design/.../addons/<name>` folders*. The **next sections** will explore how the platform uses the add-on manifest and these files during installation and runtime.

## Add-on Manifest (addon.xml) Schema 3.0

Each add-on includes an `addon.xml` file in its root that **describes the add-on and its installation schema**. CS-Cart reads this manifest to know how to install, uninstall, and manage the add-on. The current format is **Scheme 3.0**, which is supported from CS-Cart 4.2.x onwards. (Older add-ons might use Scheme 2.0, but new development should use 3.0.)

Key elements of **`addon.xml` (Scheme 3.0):**

* **Addon Identity:** The root `<addon>` tag typically includes attributes like `scheme="3.0"` and `edition_type` to specify which product editions it’s applicable to. Inside:

  * `<id>` – The add-on’s unique identifier, which **must exactly match the directory name** of the add-on. This ID is used in code (e.g., in hooks, settings, registry entries).
  * `<version>` – Add-on version number, used for display and upgrade tracking.
  * `<default_language>` – (Optional) Default language code for the add-on’s textual content (defaults to English if not specified).
  * `<priority>` – Load priority order (numeric). Higher priority values mean the add-on is loaded **later** in the sequence. CS-Cart loads add-ons in ascending order of priority, so an add-on with priority 100 loads after one with priority 10. This affects hook execution order and is important for overriding behavior (a later-loading add-on can override earlier ones).
  * `<status>` – The default status after installation: usually “disabled” (or explicitly set “active” if the add-on should auto-activate). If set to “active” in XML, CS-Cart will attempt to activate it immediately after install (subject to dependency checks).
  * `<auto_install>` – Specifies product editions where the add-on should be automatically available. For example, `ULTIMATE` or `MULTIVENDOR` (or both) can be listed here. This ensures the add-on appears on the add-ons list for those editions by default.

* **Compatibility Block:** `<compatibility>` defines environment compatibility:

  * `<core_version>` – Minimum and maximum CS-Cart version supported. If the running version is outside this range, installation will be refused.
  * `<core_edition>` – Specifies if the add-on is limited to a certain edition (e.g., `MULTIVENDOR` only).
  * `<php_version>` – Min/max PHP versions supported.
  * `<php_extensions>` – Requirements or conflicts for PHP extensions. Within this, list extensions as `<extension_name>` with either `<supported>Y</supported>` to require or `<supported>N</supported>` to mark as incompatible. You can also specify extension versions via `<min>`/`<max>` inside an extension tag.
  * `<dependencies>` – Comma-separated list of other add-on IDs that *must be installed first*. If dependencies are missing, the add-on won’t install (an error is shown).
  * `<conflicts>` – Comma-separated list of add-ons that conflict with this one. During activation, CS-Cart will disable the conflicting add-ons (with notification) to prevent incompatibility.

* **Settings:** `<settings>` defines configurable settings for the add-on. This section allows your add-on to present settings in the Admin → Add-ons → Settings UI.

  * The `layout` attribute can be `"popup"` or `"separate"` to control how the settings page appears (popup dialog vs full page).
  * Inside `<settings>` you can define `<sections>` (tabs) and within them, `<item>` entries for each setting. Each `<item>` has an `id` (key used in code via `Registry::get('addons.addon_id.setting_id')`), a `<type>` (input, checkbox, selectbox, etc.), and optionally choices (`<variants>`) if it’s a select type, default value, and language <name> for the label. CS-Cart will create these settings in the database upon installation and handle their CRUD in the admin UI.

* **Language Variables:** The add-on’s name and description (and any other language-specific strings) are typically stored in separate language files (`var/langs/..../addons/<addon_id>.po` for each language). In Scheme 3.0, the human-readable name and description for the add-on as shown in the admin Add-ons list come from those PO files, not directly from addon.xml. For example, `var/langs/en/addons/<addon_id>.po` would contain a `addon_<addon_id>_name` and `addon_<addon_id>_description` entry. When listing add-ons, CS-Cart will use these translations (falling back to English if needed). Ensure you include at least an English PO file for your add-on’s metadata.

* **Installation Scripts:**

  * `<queries>` – This section contains SQL queries to execute on install/upgrade or uninstall. Each query is listed as an `<item>` with an optional `for` attribute (values can be `"install"`, `"update"`, or `"uninstall"`, etc. – if omitted, default is install). Use the `?:` prefix for table names in these queries so that CS-Cart replaces it with the actual database prefix at runtime. Typical use: creating new tables or altering existing tables on installation, and dropping those tables on uninstall. For example:

    ```xml
    <queries>
       <item for="install">CREATE TABLE IF NOT EXISTS ?:myaddon_data (...)</item>
       <item for="uninstall">DROP TABLE IF EXISTS ?:myaddon_data</item>
    </queries>
    ```

    The installer will run the appropriate queries in a transaction-like manner; if any fail, installation is aborted and rolled back.
  * `<functions>` – Defines PHP functions to execute at specific stages. Each `<item>` has a `for` attribute similar to queries. For instance, `<item for="before_install">fn_myaddon_install</item>` could be used to run some custom PHP logic before the add-on’s database changes and settings are applied. Common uses: seeding initial data, adjusting system settings, or cleanup tasks on uninstall. The functions must be implemented in the add-on’s PHP files (e.g., in `func.php`) and typically prefixed with `fn_<addon_id>_...`. During installation, CS-Cart will call these in the order specified (all `before_install` functions first, then run queries, then `install` functions, etc.). If any function returns a database error or critical failure, installation will stop and roll back.

* **Other Elements:** There are additional optional sections like `<description>` (for a brief text description, though often handled via language variables), `<unmanaged>` flag (if set, the add-on is hidden from the Add-ons UI and can only be managed via CLI – used for built-in essential add-ons), `<files>` (if the add-on needs to ensure certain files are present, rarely used), and layout schemas (`layouts.xml` in the add-on folder) for adding default layouts. If your add-on includes storefront layout changes (blocks, grids), you can provide a `layouts.xml` which is imported on installation.

**Add-on Installation Process (High-level):** When an admin installs an add-on via the CS-Cart interface:

1. CS-Cart reads `addon.xml`. If it fails to parse, the add-on is ignored.
2. It checks compatibility: CS-Cart version, PHP, required extensions must meet the addon’s `<compatibility>` criteria.
3. It verifies dependencies: any add-ons listed in `<dependencies>` must already be installed (or the installation stops with an error).
4. The add-on’s folder is registered in the system autoloader (`Tygh::$app['class_loader']`), enabling any of the add-on’s PHP classes to be loaded automatically.
5. It runs any `<functions>` with `for="before_install"` if defined.
6. It creates a minimal record in the `?:addons` database table (with status “Disabled” and stores the priority).
7. It executes `<queries>` marked for install (e.g., creating DB schema).
8. It inserts default add-on settings into the database as defined in `<settings>`.
9. It registers language variables from the add-on’s PO files for all languages.
10. It copies design files from `var/themes_repository` to the live `design/themes` directories (for each active theme). This means your add-on’s customer-area templates and mail templates are deployed to be immediately usable.
11. It finalizes setup: runs `<functions for="install">` to perform any post-DB setup actions.
12. If the addon.xml specified `<status>active</status>`, CS-Cart then proceeds to **activate** the add-on (equivalent to the admin clicking “Enable”). Activation steps are detailed in the next section but include checking conflicts and executing special hooks.
13. If a `layouts.xml` was provided, it will import any new layouts or grid structure into the block manager.
14. Clears cache and the add-on is now installed (and possibly active).

**Upgrade and Uninstall:** The manifest also guides how upgrades (new addon version) are handled — typically via `<queries for="update">` and corresponding version checks — and how to uninstall (dropping tables, removing settings via `<queries for="uninstall">` and `<functions for="uninstall">`). When uninstalling, CS-Cart will:

* Run `<functions for="uninstall">` (giving the add-on a chance to clean up custom data or revert changes).
* Run `<queries for="uninstall">` to drop any added schema (tables, fields).
* Remove language variables and settings related to the add-on from the DB.
* Remove the add-on’s templates from active theme directories.
* Mark the add-on as uninstalled (removing its record from `?:addons`).

Understanding the addon.xml structure is crucial: it is the blueprint of your add-on. It declares not only metadata but also how your add-on integrates with the system (settings, DB changes, dependencies). Always keep it up to date when your add-on changes (e.g., if you add new settings or new required hooks, ensure the manifest reflects that).

## Module Loading and Initialization

Once an add-on is **installed**, it can be enabled (activated) or disabled via the admin panel. The **loading sequence** of add-ons during a page request and the initialization of their functionality is a core aspect of CS-Cart’s architecture.

**Add-on Listing and Detection:** When CS-Cart builds the Add-ons list in the Admin panel, it scans `app/addons/` for subfolders:

1. Every subfolder is presumed to be an add-on. CS-Cart attempts to load each `addon.xml` to gather its metadata.
2. If `addon.xml` is missing or malformed, that folder is ignored.
3. For Scheme 3.0 add-ons, the human-readable name is fetched from the language files (`addons/<addon_id>.po`) as mentioned.
4. Already-installed add-ons have their details (like description and name) cached in the database, which won’t be overwritten by changes in addon.xml until reinstalled.

**Activation (Enabling an Add-on):** Activating an add-on means turning it on (so its code will execute on page loads). When you click “Enable” for an installed add-on, CS-Cart performs these steps:

1. Load the add-on’s scheme (addon.xml) to have current data (though by this time it’s already installed, this double-checks status and any unmanaged flag).
2. If the add-on is marked `unmanaged` (a special flag in XML), it cannot be enabled via UI (only via command line).
3. Execute the hook `update_addon_status_pre` – this allows other add-ons or the system to react before status changes.
4. Check for conflicts: If any *active* add-on is listed in this add-on’s `<conflicts>`, activation is aborted (with a warning). The conflicting add-on would need to be disabled first.
5. If defined, call the function `fn_settings_actions_addons_<addon_id>()` with parameters `(new_status, old_status, on_install)`. This is a convention where an add-on can define a special PHP function to run custom code when its status changes (e.g., enabling might require generating default data). If `on_install` is true, it indicates this activation is happening immediately after install.
6. Ensure any add-ons *this* add-on conflicts with are disabled (if not, abort).
7. Update the database: set this add-on’s status to “A” (Active) in the `?:addons` table.
8. If defined, call `fn_settings_actions_addons_post_<addon_id>()` with `(new_status)` as parameter. This is another optional hook function for after activation tasks.
9. If the add-on had any product detail tabs (for product pages) defined via templates, those are activated (made visible) now.
10. Set `Registry::set('addons.<addon_id>.status', 'A')` for runtime usage.

**Deactivation (Disabling):** Similarly, when disabling, CS-Cart will call `fn_settings_actions_addons_<addon>()`, update status to “D”, call `fn_settings_actions_addons_post_<addon>()`, and hide any UI components related to it.

**Runtime Loading Order:** During every page request, the system initializes **all active add-ons**. The sequence is as follows:

* CS-Cart’s front controller (admin.php or index.php) runs `init.php` which sets up the environment and then calls the dispatcher (`fn_dispatch()`).
* Early in the initialization (within `init.php` or related includes), it will load each **active** add-on’s `init.php` file. The order of loading is determined by the add-ons’ priority: lower priority numbers load first, higher numbers last. This means an add-on with priority 5 will have its init run before one with priority 100.
* Loading an add-on’s `init.php` typically registers that addon’s hooks via `fn_register_hooks()`, sets up autoload (if not already done during install), and can perform other start-up tasks. The add-on’s `init.php` is only included if the add-on’s status is Active (A).
* Additionally, CS-Cart merges any schema files from the add-on at this time. For example, an add-on’s `schemas/menu/menu.post.php` that adds a menu item will be included to augment the menu schema, an add-on’s `schemas/permissions/*.php` will update permission definitions, etc. This merging usually happens lazily when the respective functionality is initialized (e.g., when rendering the admin menu, it loads all `menu.post.php` from active add-ons).
* The class autoloader is already aware of the add-on’s directory from installation, so any class instantiation (e.g., using a class defined under `app/addons/<addon>/`) will be auto-loaded without additional includes.
* Finally, the requested controller runs (see next section), and during execution, any hooks triggered will now invoke the add-on’s hooked functions (registered via `init.php`).

**Add-on Dependencies and Load Order:** If Add-on A depends on B (declared in addon.xml), it is expected B is installed first. CS-Cart does not automatically reorder installation, but it will prevent installing A until B is present. At runtime, *if both are active*, their relative execution order can matter. The **priority** attribute is the main tool to manage execution order. By giving a higher priority to a dependent add-on, you ensure it loads after its dependency. Also, the hook system itself can accommodate dependency: you can register a hook that only runs if another add-on is active (see **Extending other add-ons** under Hooks below).

In summary, when creating an add-on:

* Set a sensible priority (most third-party add-ons might use default like 100; only change if you need a specific order).
* Declare any required add-ons in `<dependencies>` to avoid user error.
* Expect that when your add-on is enabled, its `init.php` will execute on every request, so keep it efficient (just registering hooks and minimal setup).
* The platform ensures that by the time the request is handled, all add-on hooks are registered and their schema contributions loaded, so your custom functionality can seamlessly hook into the core execution flow.

## Controllers and Request Flow

CS-Cart uses a **dispatch mechanism** to handle web requests. The front controllers `admin.php` (for backend) and `index.php` (for storefront) route requests to the appropriate **controller** based on a `dispatch` parameter. Understanding how controllers work is crucial for both using core controllers and adding your own.

**Dispatch & Controller Resolution:** A request URL typically contains a query like `?dispatch=controller_name.mode_name`. CS-Cart parses this and determines which PHP file to execute:

* If the request is to the admin panel (via `admin.php`), it will look under `app/controllers/backend/` for `controller_name.php`.
* If it’s a storefront request (via `index.php`), it will look under `app/controllers/frontend/` for `controller_name.php`.
* If not found in the area-specific folder, it will fallback to `app/controllers/common/` (common controllers that serve both areas).
* The controller PHP file is included and executed. Within that file, the `$mode` variable is set to the mode\_name from dispatch (and `$action` might be set if a second part like `dispatch=controller.mode.action` is present).
* Example: `admin.php?dispatch=products.manage` will load `app/controllers/backend/products.php` (the **Products** controller for admin). Inside that file, logic will handle the `"manage"` mode (typically by retrieving product list and assigning it to a template).
* **Uniqueness:** Controller names must be unique across all add-ons and core. If an add-on tries to define a controller with the same name as an existing one, it will cause a conflict in dispatch resolution. Therefore, choose unique names for new controllers (often prefixed or namespaced via your add-on name) to avoid collisions.

**Controller Structure:** A controller file is essentially a script that may contain multiple sections:

* **POST request handling:** Usually at the top, the controller checks `if ($_SERVER['REQUEST_METHOD'] == 'POST')` and handles form submissions or actions. After processing a POST (which might involve calling core functions to save data), the convention is to **redirect** or set a proper return status. This is done by returning an array like `return [CONTROLLER_STATUS_OK, "redirect_url"];` which tells CS-Cart to redirect to a given page after the POST is processed. This prevents form resubmission and updates the page.
* **GET request handling:** Following any POST block, the controller handles different modes for GET requests. Typically, a series of `if ($mode == 'view') { ... } elseif ($mode == 'manage') { ... }` conditions. Each mode corresponds to a particular page or action. For instance, `$mode == 'manage'` might gather data for listing items, while `$mode == 'update'` might fetch a single item for editing.
* Controllers may define local helper functions (though usually core controllers keep logic in global functions for reuse).
* After the controller script runs, control returns to the dispatcher, which will automatically assign the template to display (unless a redirect or AJAX response occurred). CS-Cart by convention uses a template with the same name as the controller and mode: e.g., for `products.manage`, it looks for `design/backend/templates/views/products/manage.tpl`. If an add-on controller was `my_new_page.add`, it would look for `design/backend/templates/addons/<addon_id>/views/my_new_page/add.tpl`.

**Adding New Controllers (Add-on pages):** Your add-on can introduce new pages in both admin and storefront:

* For an **Admin page**: create a file `app/addons/<addon_id>/controllers/backend/<your_controller>.php`. In this file, handle whatever modes you need (e.g., `manage`, `add`, `update` for listing or forms). Next, create corresponding templates under `design/backend/templates/addons/<addon_id>/views/<your_controller>/<mode>.tpl` for each mode. Also, to make the page accessible via menu, extend the admin menu schema via `schemas/menu/menu.post.php` in your add-on to insert a menu item that links to `dispatch=<your_controller>.<mode>`. Once the add-on is installed, the new controller becomes accessible (e.g., via direct URL or menu).
* For a **Storefront page**: similarly, add `app/addons/<addon_id>/controllers/frontend/<your_controller>.php` and define logic for `$mode` (likely at least a default mode like `view` or `manage`). Then add templates under `design/themes/<theme>/templates/addons/<addon_id>/views/<your_controller>/<mode>.tpl`. You might link to this page via a new link on the storefront (e.g., adding a link in a block or a menu). If it should appear in the customer’s top menu, you can use `schemas/menu/menu.post.php` as well (the storefront has its own menus).
* **Permissions:** If the new controller should be restricted by user roles (admin or vendor privileges), ensure you add entries in `schemas/permissions` for it. For admin controllers, you might not need an entry if it falls under existing section privileges, but for vendors or specific usergroups, define the rules.

**Pre- & Post-Controllers (Extending Core Controllers):** CS-Cart provides a mechanism to insert logic before or after a core controller executes, *without modifying the core file*. In your add-on’s controllers directory, you can create **pre** or **post** controller files:

* A **pre-controller** runs *before* the main controller, a **post-controller** runs *after* it.
* Naming convention: `[controller_name].pre.php` or `[controller_name].post.php`. Place the file in the corresponding area folder (backend or frontend) of your add-on. For example, to run code before the **Products** controller in admin, create `app/addons/<addon_id>/controllers/backend/products.pre.php`. To run after it, use `products.post.php`.
* When CS-Cart dispatches a controller, it **collects all add-on pre/post controllers** for that controller and executes them in order: all pre-controllers (by priority) first, then the core controller, then all post-controllers. This is orchestrated by `fn_dispatch()` which looks up matching files in enabled add-ons.
* In the pre/post file, you have access to the same variables as the main controller (since they’re in the same scope). Common use: modify input parameters before the core logic runs (pre), or adjust the output/assigned template variables after core logic (post).
* Example: The Attachments add-on might have `products.post.php` in its backend controllers. That file executes after `backend/products.php` finishes, allowing the add-on to append attachments info to the product data before the template is rendered. If you wanted to run logic *before* product controller (say, to redirect or set some data early), `products.pre.php` would do that.
* **Important:** You can only make pre/post controllers for existing controller names. If you create a pre/post for a controller that doesn’t exist, it won’t be triggered. Conversely, if you accidentally name your add-on’s new controller the same as a core one, you can’t have two controllers of the same name active – one will override or cause errors. So use pre/post naming only to extend, not to create new conflicting controllers.

**Controller Best Practices:**

* Keep heavy logic out of controllers – put it in `fn_*` functions (either in core or your add-on’s `func.php`). Controllers ideally orchestrate calls and prepare data.
* Always sanitize and validate input in controllers (CS-Cart has built-in input filtering, but you should still be careful).
* Use CS-Cart’s built-in redirect pattern after handling POST (as shown with `CONTROLLER_STATUS_OK`) to ensure a smooth UX without duplicate form submissions.
* For admin controllers, ensure you have proper permission checks. CS-Cart by default will check `_REQUEST['dispatch']` against the permission schema for the current admin’s privileges, so if you added a new section, update `schemas/permissions`.
* Pre/Post controllers should ideally be small wrappers. Don’t duplicate core logic in them; just augment inputs or outputs. If something requires significant processing, consider if a hook (which can be more targeted inside a function) is more appropriate than a post-controller.

In summary, controllers in CS-Cart define *pages and actions*. Add-ons can add new pages via their own controllers or tweak existing ones via pre/post controllers. The dispatch system automatically finds these based on naming and the `dispatch` request, making integration quite seamless as long as you follow the naming conventions and structure.

## Hooks and Extension Points

A major strength of CS-Cart’s architecture is the **hook system**, which allows add-ons to inject or modify functionality without altering core code. CS-Cart implements hooks in both PHP code (logic hooks) and in Smarty templates (template hooks). Additionally, it provides a way to completely override templates if necessary. Using hooks is the preferred way to extend CS-Cart safely.

### PHP Hooks (Code Hooks)

**What is a PHP Hook?** – It is a point in the core PHP code where execution can be transferred to add-on code. Developers of CS-Cart insert calls like `fn_set_hook('hook_name', $param1, $param2, ...)` at various places in core functions to allow intervention. When `fn_set_hook('some_hook')` is executed, the system will call any functions defined by add-ons that are listening to `'some_hook'`.

**Using PHP Hooks in an Add-on:** To utilize a hook:

* **Register the hook in `init.php`:** Call `fn_register_hooks('hook_name');` within your add-on’s init file for each hook you want to use. You can register multiple hooks in one call or separate calls. This tells CS-Cart that when the hook `'hook_name'` occurs, your add-on has a handler.
* **Define the Hook Handler in `func.php`:** The handler is a function named by convention: `fn_<addon_id>_<hook_name>( & $var1, $var2, ... )`. Essentially, prepend `fn_` and your add-on ID to the hook name. The parameters of this function should match those passed to `fn_set_hook` (with references where needed). For example, if the hook is called as `fn_set_hook('get_category_data_pre', $category_id, $field_list)`, your add-on would:

  * In init.php: `fn_register_hooks('get_category_data_pre');`
  * In func.php: implement `function fn_myaddon_get_category_data_pre(&$category_id, &$field_list) { ... }`.
* **Inside your hook function**, you can alter the passed-in variables (notice they might be passed by reference, so your changes affect the caller) or perform other actions at that point. When your function finishes, control returns to the core code after the hook call.

**Hook Execution:** When `fn_set_hook('hook_name', ...)` is invoked:

* CS-Cart iterates through all active add-ons that registered this hook and calls their `fn_addon_hook_name()` functions in the order of add-on priority. This means lower-priority add-ons run their hook first, higher priority last (since higher priority loads later). If multiple add-ons hook the same point, you might need to consider execution order or conflict (rarely, you might see add-ons adjusting the same data).
* All variables passed to `fn_set_hook` become available to your function. If you modify a reference parameter, those changes persist when execution returns to core.
* Hooks do not inherently provide a return value; they are intended to alter state or perform side effects. The core might check results via modified variables.

**Finding Hooks:** CS-Cart has hundreds of hook points. You can find them by searching the source for `fn_set_hook(` or refer to the official **Hook Database** which lists hooks by version. For instance, hooks exist for most key operations (before saving an order, after adding to cart, before rendering product detail, etc.). Always use an existing hook if possible, rather than modifying core logic.

**Example:** The Gift Certificates add-on uses a hook in the `fn_get_gift_certificate_info` function. Core calls `fn_set_hook('get_gift_certificate_info', $_certificate, $certificate, $type)` and the Gift Certificates add-on implements `fn_gift_certificates_get_gift_certificate_info($_certificate, $certificate, $type)` to supplement that data. This pattern is ubiquitous: hook names are usually descriptive of the point in code, and add-ons prefix their ID to the function to claim the hook.

**Extending Other Add-ons via Hooks:** Uniquely, CS-Cart allows an add-on to hook into another add-on’s functionality. For example, the SEO add-on might extend the Blog add-on via hooks. This is done in two ways:

* You can place template hooks under an **`addons/`** directory in templates (covered below).
* For PHP hooks, you can specify a third parameter in `fn_register_hooks`: the add-on dependency. E.g., `fn_register_hooks(array('get_news_data', '', 'seo'));` in the News add-on means “register a hook for get\_news\_data only when the SEO add-on is active”. The handler function in that case would be named as if it belonged to SEO: `fn_seo_get_news_data()`, and it will execute only if SEO is enabled. Essentially, one add-on can house hooks that modify another add-on’s behavior.
* Alternatively, you can check within your hook handler if another add-on is active (via `Registry::get('addons.addon_name.status')`) if you only want to run when both are on.

Using PHP hooks effectively allows add-ons to **inject logic into almost any process**: validating data, altering queries, adding new fields to forms, etc., all without touching core code. Always maintain the naming and parameter conventions, and consider edge cases (e.g., if your hook relies on something that may not always be present, guard it with conditions).

### Template Hooks (Smarty Hooks)

CS-Cart’s templating system also provides hooks to let add-ons modify the HTML output. **Template hooks** are defined in core (or base theme) Smarty `.tpl` files and can be overridden or supplemented by add-on templates.

**Core Template Hook Definition:** In a template, a hook is defined with:

```smarty
{hook name="identifier:hook_name"}
  ... default content ...
{/hook}
```

For example, in an order management template, you might see:

```smarty
{hook name="order_management:product_info"}
   ... HTML for product info ...
{/hook}
```

. Here `"order_management:product_info"` is the hook identifier (usually formatted as `template_section:hook_name`).

By default, the content inside the `{hook}...{/hook}` tags is what the core displays. But an add-on can inject additional content before or after this, or completely replace it.

**Using a TPL Hook in Add-ons:** Unlike PHP hooks, template hooks do **not** require registration via init.php. They work by **naming convention and file placement**:

* To add content **before** the core hook content, create a template file in your add-on:

  * For admin area: `design/backend/templates/addons/<addon_id>/hooks/<template_section>/<hook_name>.pre.tpl`.
  * For storefront: `design/themes/<theme>/templates/addons/<addon_id>/hooks/<template_section>/<hook_name>.pre.tpl`.
* To add content **after** the core content: create `<hook_name>.post.tpl` in the same manner.
* To **override** (replace) the core content entirely: create `<hook_name>.override.tpl`.

CS-Cart will automatically include these hook templates at the correct hook points:

* If an `.override.tpl` exists for a given hook, the core content inside `{hook}` is skipped entirely, and only the override from the add-on is used.
* If `.pre.tpl` and/or `.post.tpl` exist, the core content is rendered, and the add-on’s pre template is inserted before it, post template after it.
* Multiple add-ons can use the same hook; `.pre.tpl` from all add-ons (in order of add-on priority) will render, then core content, then all `.post.tpl` in order.
* If multiple overrides exist (which is rare, as typically only one add-on would override a given hook), the one with highest priority likely wins; however, using override in multiple add-ons on the same hook is not recommended (it’s effectively conflict – only one can take effect).

**File Placement:** The file path after `hooks/` should mirror the template’s location or logical grouping (the part before the colon). For instance, a hook named `"index:styles"` in the storefront (maybe to add styles in the `<head>`) would correspond to path `hooks/index/styles.post.tpl`. The core documentation or naming usually makes it clear which template or area is being hooked. In admin vs customer, ensure you put the file in the correct design folder (backend vs theme).

**Example:** To override the footer in the storefront, if the core template uses a hook `{hook name="index:footer"}` wrapping the default footer content, your add-on could provide `design/themes/<theme>/templates/addons/<addon>/hooks/index/footer.override.tpl` with your own footer HTML. Or if you just need to add a script at the bottom of the page, `footer.post.tpl` might suffice (which appends content after the existing footer).

Template hooks are ideal for injecting banners, adding new info to product pages (e.g., an add-on adding a new product attribute can display it via a hook on the product detail template), inserting scripts or analytics, etc.

**Overriding Entire Templates:** In cases where no suitable hook exists, CS-Cart allows full template overrides through the **overrides/** directory mechanism:

* In your add-on’s admin or theme template folder, create an `overrides/` directory that mirrors the path from `design/.../templates/` to the target template.
* Place a copy or a new version of the core template file there. When CS-Cart renders that template, it will see an override exists and use the add-on’s version instead.
* Example: To override `design/backend/templates/views/products/update.tpl` (the product editing page in admin), you would create `design/backend/templates/addons/<addon>/overrides/views/products/update.tpl`. The presence of this file means whenever the product edit page is shown, CS-Cart will load your template in place of the original.
* **Important:** Overriding is powerful but should be used sparingly because it completely replaces core content, which might lead to maintenance issues when CS-Cart updates that template in a new version. Always check if a hook could achieve the same goal first. If using overrides, clearly document it and be prepared to update your override for new CS-Cart versions.

**Hook Best Practices:**

* Use `.pre` and `.post` hook templates to unobtrusively add content. For instance, if you need to add a block of HTML to an order invoice, see if there’s a hook in the invoice template and use pre/post around the relevant section.
* Use `.override` only when you need to fundamentally change the structure or remove existing content.
* Clean up after your hooks if needed: e.g., if you add JavaScript via hooks, ensure it doesn’t conflict with other scripts.
* Keep design consistency: follow the core’s markup structure in your hook templates (use similar classes or grid system if applicable).
* When multiple hooks could be used (PHP vs TPL) to achieve something, decide where it’s best to implement. E.g., adding a new field to a form might require a PHP hook (to process data) and a TPL hook (to show the field). Use both appropriately.
* If CS-Cart lacks a needed hook in core, you can request it from CS-Cart (they have a process to add new hooks in updates). Meanwhile, the alternative would be an override, but adding a hook is preferable for long-term.

Using hooks, your add-on can **safely** extend virtually any part of CS-Cart: from modifying data logic (PHP hooks) to altering the user interface (TPL hooks and overrides). This design allows multiple add-ons to coexist and customize the platform in a modular way. A well-written add-on leverages hooks to avoid direct core changes, thus remaining compatible with core updates.

## Database Conventions and Integration

When developing add-ons that interact with the database, it’s important to follow CS-Cart’s established database schema conventions to maintain consistency and compatibility. CS-Cart’s database is well-structured with clear naming patterns, especially to handle multi-language content and relational data.

**Table Naming Conventions:**

* Tables that represent a primary entity are named in **plural form** of that entity. e.g., `products` (stores base product info), `orders`, `users`.
* Tables that store supplementary or related information use the form **`<entity>_<suffix>`**, where suffix is plural if it’s a list. For example:

  * `product_prices` – prices for products (additional pricing info, related to `products`).
  * `order_details` – line items for orders.
  * `user_profiles` – additional profiles for users.
* When relationships go multiple levels deep, the convention is to keep the last part plural and earlier parts singular (for readability). For example: `product_features` (features of products), `product_feature_variants` (variants of those features), `product_feature_variant_descriptions` (translations for feature variant names). Notice how only the last word is plural in that last example (descriptions) following the rule that the deepest suffix retains plural form.

For your add-on’s custom tables, choose names following these guidelines. If you add a main entity, name the table plural. If you have related tables, use suffixes. Avoid overly long names; abbreviations are okay if obvious.

**Table Prefix and Query Syntax:**

* CS-Cart allows a configurable table prefix (default `cscart_`). All official tables are prefixed in the actual DB (e.g., `cscart_products`). In code and schema definitions, they reference tables with a `?:` placeholder that gets replaced by the prefix at runtime.
* When writing SQL in PHP or in addon.xml `<queries>`, **always use `?:` prefix** for table names. For example, `db_query("SELECT * FROM ?:products")` will correctly query `cscart_products`. This ensures your add-on works even if the store was installed with a custom prefix.
* CS-Cart’s database API (the `db_...` functions) supports this placeholder as shown. It’s a simple find-and-replace for the configured prefix.

**Primary Keys and Foreign Keys:**

* Primary key columns are named as `<entity>_id` (singular entity name + “\_id”). E.g., `product_id` in `products` table, `feature_id` in `product_features` table.
* Foreign key fields use the same naming (the primary key name of the referenced table). So if `order_items` table references orders, it should use `order_id` as the field name (matching `orders.order_id`). This is a convention; actual SQL foreign key constraints may not be used in schema, but naming helps identify relationships.
* Auto-increment integer IDs are common for PKs.
* If your table’s primary key is a composite, ensure naming reflects each part properly.

**Multi-Language Data:**
CS-Cart heavily uses separate tables to store translations for any object that has language-specific content (product names, category names, etc.). The pattern:

* For an entity `X` that has translatable fields, there is a table `x_descriptions`.
* The `*_descriptions` table typically includes:

  * An ID column referencing the main entity (e.g., `product_id`).
  * A `lang_code` column (char(2)) to indicate the language of that row.
  * One or more columns for each translatable field (e.g., `product` for name, `description`, `meta_keywords`, etc. in product\_descriptions).
* Each time an entity is created, entries are created in the descriptions table for each active language (or at least for the language being edited).
* When fetching data to display on the storefront, CS-Cart joins the main table with the description table, filtering by `lang_code = CART_LANGUAGE` (the current frontend language). Similarly, in admin, it might use `lang_code = DESCR_SL` (selected language for editing).
* If you create a new entity with translatable fields in your add-on, consider using the same approach:

  * Create a main table and a separate `<entity>_descriptions` table.
  * Use `lang_code` column in the descriptions table.
  * Ensure your add-on’s functions insert and retrieve data with respect to language. (You can use CS-Cart’s `db_quote` and helper functions for multi-lang; or use the existing pattern: e.g., look at how `fn_update_product` handles descriptions.)
* This design avoids adding multiple language columns to main tables and makes it easy to extend languages.

**Storage Engine (MyISAM vs InnoDB):**

* Historically, CS-Cart used MyISAM for all tables (due to broad compatibility and simpler backup/restore).
* Modern CS-Cart (since 4.13+) might support or even default to InnoDB for better transaction and row-locking behavior (this documentation notes MyISAM but acknowledges InnoDB advantages). In fact, many CS-Cart installations now run InnoDB for improved reliability.
* **Foreign Key Constraints:** CS-Cart does not rely on SQL foreign key constraints or ON DELETE CASCADE rules in schema, even with InnoDB. They explicitly warn that using such constraints can conflict with CS-Cart’s internal logic that already handles cascading deletes in PHP. For example, deleting an order via CS-Cart code will manually remove related items, shipments, etc. If you also had an SQL foreign key with cascade, it could double-trigger or cause errors. Thus:

  * It’s recommended *not* to create actual SQL foreign keys in your add-on’s tables. Maintain referential integrity through code (like core does).
  * If you switch tables to InnoDB for performance, be cautious adding constraints; rely on CS-Cart events/hooks to clean up related data.
* All tables use `utf8` (or `utf8mb4` in newer versions for full Unicode) charset. Ensure your add-on’s table creation uses the same charset/collation for consistency.

**Database API and Usage in Add-ons:**

* CS-Cart provides a set of database functions (in `app/functions/fn.database.php` and via the Tygh\Database class). Common ones:

  * `db_get_field($query, ...params)` – returns a single value.
  * `db_get_row($query, ...params)` – one row (assoc array).
  * `db_get_array($query, ...params)` – multiple rows.
  * `db_query($query, ...params)` – execute an INSERT/UPDATE/DELETE.
  * These functions handle the `?:` replacement for prefix and some basic quoting (though you should still pass parameters for values to avoid SQL injection).
* Use these instead of direct `$mysqli` calls. They also handle caching of queries in some cases.
* Also, when manipulating multi-lang data, note that CS-Cart often provides higher-level functions. E.g., `fn_update_product` will update both `products` and `product_descriptions`. If you add similar concept, you may create your own combined update function.

**Cleaning up on Uninstall:** If your add-on creates new tables or adds fields to existing tables, you should remove them on uninstall via `<queries for="uninstall">` in addon.xml. For added fields in core tables, you might opt to leave them (to avoid data loss if re-installed), but usually it's cleaner to drop any schema you added. Always back up or ask the user to back up data if needed.

**Example Custom Table:** Suppose you create an add-on “Vendor Badges” that awards badges to vendors. You might have:

* `cscart_vendor_badges` (badge\_id, badge\_name, icon).
* `cscart_vendor_badges_descriptions` (badge\_id, lang\_code, badge\_name, description).
* `cscart_vendor_badges_to_vendors` (badge\_id, vendor\_id, granted\_at).
  Here we see plural names, an association table for many-to-many relationship, and a descriptions table for multi-language fields. If implementing, you’d:

  * Use prefix `?:vendor_badges` in queries.
  * Possibly integrate with existing vendor tables via foreign key (not enforced, but logically vendor\_id refers to cscart\_companies.company\_id in Multi-Vendor).
  * Provide appropriate hooks or admin UI to manage these badges.

By adhering to CS-Cart’s DB structure conventions (plural naming, prefix placeholders, separate description tables), your add-on’s database changes will feel native to the platform and reduce the risk of conflicts or confusion. This also aids other developers or store maintainers in understanding and maintaining the database.

## Core Functions and Utilities

CS-Cart’s **core functions** (those prefixed with `fn_`) and core classes provide a rich API for add-on developers. Rather than writing raw SQL or reinventing features, you can often call existing functions to manipulate data (products, orders, etc.) or use core classes for common tasks (sending mail, authenticating users, etc.). Here we outline the key aspects of core functions and how to leverage them.

**Location of Core Functions:** Core procedural functions are located in the `app/functions/` directory, organized by theme:

* `fn.catalog.php` – functions related to product catalog (products, categories, features).
* `fn.cart.php` – functions for the shopping cart and checkout process.
* `fn.users.php` – functions for user management (customers, admins, user profiles).
* `fn.common.php` – general utility functions used across the platform (string formatting, array helpers, etc.).
* `fn.cms.php` – content management (pages, etc.), if present.
* `fn.notifications.php` – possibly for notifications.
* etc. Each file groups logically related functions.

These functions are **automatically included** as needed (commonly via `init.php` or when certain subsystems are invoked). You don’t typically need to include them manually. If you call a function and it’s not defined yet, ensure you have included the relevant file or triggered the subsystem (in practice, during normal dispatch, relevant function files are already included by controllers or init).

**Using Core Functions:** Check the developer guide or the code to find if a function exists for what you need. For example:

* To get product data, `fn_get_product_data($product_id, $auth, $lang_code, $field_list, $get_additional, $preview, $lang_code)` might exist (just as an example).
* To update a product, `fn_update_product($product_data, $product_id, $lang_code)` handles saving a product and all linked info.
* For orders, `fn_change_order_status($order_id, $status, $manager_id, $notify)` changes status and triggers notifications.
* Many of these functions will trigger hooks internally. For instance, `fn_add_to_cart` might call hooks like `pre_add_to_cart` or `post_add_to_cart`.

Always consider hooking rather than overriding a function. The **Core functions page** notes that PHP hooks are used to extend/override default behavior. So if you find a core function that almost does what you need but not exactly, see if it calls `fn_set_hook` at a strategic point. Often, they call a hook before finalizing the result, allowing your add-on to modify the outcome. For example, `fn_calculate_cart_content` uses hooks to adjust the cart before totals calculation, etc.

**Global Variables and Registry:** Some core functions rely on global state or the `Registry`:

* `Registry::get('settings.Company.company_name')` – access to configuration settings.
* `Registry::get('addons.addon_id.setting_name')` – after install, addon settings are accessible here.
* There are also constants like `AREA` (set to 'A' for admin, 'C' for customer area), `ACCOUNT_TYPE` (for vendor vs admin in Multi-Vendor), etc., that core functions use. Be mindful of these if writing functions that should behave differently in admin vs frontend.

**Core Classes (Tygh namespace):** Under `app/Tygh/` there are useful classes:

* **Database (Tygh\Database\Connection)** – While you can use `db_` functions, you can also get a DB instance via `Tygh::$app['db']`. It’s a wrapper (likely extending PDO or MySQLi) that supports the same placeholder replacement.
* **Registry (Tygh\Registry)** – Provides static methods for getting/setting registry values. As mentioned, it’s now mainly for storing runtime data or config, not objects.
* **Storage / FileStorage** – manages file access, especially for files stored in `var/files` or CDN.
* **Mailer (Tygh\Mailer)** – sending emails based on email templates. Instead of using `mail()` directly, use this to integrate with CS-Cart’s mail template and queue system.
* **Ajax (Tygh\Ajax)** – helps form AJAX responses if you’re building an AJAX backend controller.
* **Exim (Tygh\Exim)** – import/export routines, if you integrate with CSV import/export.
* **Addons (Tygh\Addons\*)** – classes that manage add-on initialization and execution (mostly internal usage).
* **Enum** classes – Newer CS-Cart versions use enums for certain constants (like OrderStatuses, YesNo).
* **App (Tygh\Application)** – as detailed earlier, the IoC container. Most services can be accessed via Tygh::\$app short keys (e.g., `'view'` for Smarty, `'session'` for Session, etc.). If you need something from core, check if it’s a service.

**Events and Notifications:** In addition to hooks, CS-Cart introduced a formal **Event Notifications** system (v4.11+). It’s more complex but essentially allows dispatching events and having messages sent via various transports (email, onsite notification). This is used for things like "order created", "low stock" notifications. For add-ons, if relevant, you can tie into it by defining new events or sending events (the docs have details on how to dispatch and define messages). This could be useful if your add-on needs to notify admin or vendor of some action (instead of or in addition to email).

**Coding Standards:** CS-Cart follows a consistent coding style (PSR-like). A few notable standards:

* Indentation with spaces, braces on new lines (Allman style in older code).
* Function names are snake\_case (e.g., `fn_get_product_data`).
* Variables also snake\_case.
* Constants are ALL\_CAPS with underscores.
* SQL keywords uppercase in queries.
* Use of `Y`/`N` char flags in DB for booleans, or sometimes `YesNo::YES` in code (mapped to 'Y').
* It’s wise to skim CS-Cart’s `Coding Standards -> PHP` section for conventions. Writing in a similar style for your add-on keeps it coherent. For example, language variable names typically are all lowercase with words separated by underscores.

**Using Core Data Structures:**

* **Settings:** If your add-on needs configuration, use the addon settings (via addon.xml as covered). Then access via `Registry::get('addons.youraddon.your_setting')` in code.
* **Session:** CS-Cart’s session is stored in `Tygh::$app['session']` which behaves like an array/ArrayAccess. Use it to set session data, e.g., `Tygh::$app['session']['cart']` for cart contents.
* **Cache:** There’s a cache layer available: `Registry::setCache()` or via Cache factory in Tygh. If your add-on does heavy queries, consider caching results using core cache (so it respects CS-Cart cache clear events).
* **Logging:** CS-Cart has a `fn_log_event` and also uses standard PHP error\_log for some things. You can also use `fn_set_notification($type, $title, $message)` to show user/admin notifications (for success/error messages on screen).

In essence, before coding functionality from scratch, check the CS-Cart core – you might find a function or class that achieves what you need or that you can call to do the heavy lifting. For example, need to create a new order via code? There’s `fn_place_order` that handles all the order creation, email, inventory deduction, etc. Need to hash passwords? Use `fn_generate_salt`, `fn_password_hash`. By leveraging these, you ensure your add-on behaves consistently with the rest of the platform.

And finally, if core lacks some utility, consider contributing it or at least implementing it in a way that aligns with core’s structure (maybe in your add-on’s func or as a service). The more you integrate with CS-Cart’s core APIs, the less maintenance and edge-case handling you have to do.

## Class Autoloading and Custom Classes

CS-Cart’s architecture isn’t fully MVC OOP, but it does support and use classes (especially in the `Tygh` namespace and add-ons can use PHP classes as well). Understanding how **autoloader** works and how to organize your classes is important for advanced add-on development.

**Autoloader Integration:** CS-Cart uses an autoloader (compliant with PSR-4 style in many cases). When CS-Cart initializes (see `init.php`), it sets up autoloading for core classes and also **registers all add-ons’ directories** to autoload their classes. Specifically, during add-on installation, the system does:

```php
Tygh::$app['class_loader']->addDir('app/addons/<addon_id>');
```

This means any class under your add-on folder can be discovered by the autoloader without manual inclusion, as long as the class’s namespace and file path follow expected conventions.

**Namespace and File Structure:** CS-Cart core classes live under the `Tygh` namespace (e.g., class `Tygh\Shippings\Services\UPS` is located at `app/Tygh/Shippings/Services/UPS.php`). By default, CS-Cart’s autoloader will treat `app/addons/<addon>/` as a root for PSR-4 with **no specific namespace prefix**. In practice, add-on developers often put their classes under the `Tygh` namespace to extend or utilize core structure:

* For example, to add a new shipping service class, place it at `app/addons/<addon>/Tygh/Shippings/Services/Myservice.php` defining `class Tygh\Shippings\Services\Myservice`. CS-Cart will auto-load this and you can then reference it (CS-Cart might automatically pick it up if it scans that directory for shipping services).
* If you prefer a custom namespace, you can do that too (e.g., `Myaddon\Tools\MyClass` inside `app/addons/<addon>/Myaddon/Tools/MyClass.php`). As long as the path and namespace match, the autoloader should find it because the add-on directory is registered. However, using the `Tygh` namespace ensures consistency and sometimes integration (like placing under existing namespaces such as Tygh\Api... if extending API, etc.).
* The autoloader likely conforms to PSR-4, meaning if you use a vendor namespace (like `Company\Addonname\Class`), you’d need to adjust autoloader to know that (which CS-Cart’s own autoloader might not do unless configured). So sticking with `Tygh\...` or no namespace (for global functions) is simplest.

**When to Use Classes:** Use classes for:

* Large logic components that benefit from object-oriented structure (e.g., a service class to handle complex operations).
* Integrations that require classes (like Payment gateways often have classes, or 3rd party libraries).
* Controllers in CS-Cart are not class-based (they are procedural), but nothing stops you from instantiating objects inside them.
* Data models: CS-Cart doesn’t use an ORM for its core entities, but you could create your own small ActiveRecord or models if needed. However, be cautious to not conflict with core expectations – for example, core might not know about your objects unless you integrate via hooks.

**Placement of Custom Libraries:** If your add-on uses third-party libraries (like an SDK), you have a few choices:

* Place it under `app/addons/<addon>/lib/` – then manually include it (or use composer’s autoload if CS-Cart includes composer, which as of recent versions, CS-Cart does ship with a `vendor/` directory for some components).
* Place classes under your add-on such that autoloader picks them up. E.g., include the library’s classes under your namespace or under Tygh. This might mean “installing” the library by copying its src.
* Use composer: If your add-on is complex, you might manage it via composer and integrate. CS-Cart’s `app/lib/vendor` is for its own dependencies, but you could bundle composer autoload for your add-on specifically.

**Example of Custom Class Usage:** Let’s say your add-on provides an API client to an external service. You could create `app/addons/youraddon/Classes/ApiClient.php` (namespace `Youraddon\Classes\ApiClient`). To autoload this, you might need to adjust (perhaps via `init.php`, adding a path or using spl\_autoload). Alternatively, put it in `Tygh\Addons\Youraddon\Api\Client.php` (with class `Tygh\Addons\Youraddon\Api\Client`) to better integrate. (CS-Cart itself uses a pattern for add-on classes, e.g., some add-ons register classes via Tygh\Addons namespace in newer versions.)

**Tygh::\$app Container for Add-ons:** You can register your own services in the Application container if needed. For example, in your init.php:

```php
Tygh::$app['youraddon.service'] = function ($app) {
    return new Tygh\Addons\Youraddon\Service($app['db'], $app['logger']);
};
```

Then elsewhere, you can call `Tygh::$app['youraddon.service']` to get the instance. This is advanced usage but useful if your add-on has a core service that many parts (hooks, controllers) need to use. It ensures a single instance and easy access.

**Summary of Autoload Best Practices:**

* Place your classes in the add-on directory in a logical structure. The autoloader is already configured to search your add-on folder.
* Use namespaces that make sense. To avoid collision, using your add-on name as part of the namespace (or under Tygh\Addons) is wise.
* Do not rely on `require` or `include` for your own classes if you can avoid it – let the autoloader do the work.
* Remember that during installation, before activation, classes are loadable (because the folder is added to autoload). But your code likely won’t run until activation.
* You can also autoload template files if needed via design override, but for PHP, autoload covers classes only. For plain functions in func.php, those get loaded on demand (like when hook triggers or controllers call them).

In practice, many simpler add-ons might not define custom classes at all, sticking to functional programming. But as your add-on grows, organizing code into classes helps maintainability. CS-Cart’s autoload and service container provide the infrastructure to do this cleanly.

## Best Practices and Development Tips

Finally, to tie everything together, here are key best practices and patterns when developing on CS-Cart, ensuring your add-ons are robust, maintainable, and compatible with future CS-Cart updates:

* **Non-Intrusive Development:** *Never modify core CS-Cart files*. This cannot be overstated. All changes should be via add-ons. Use hooks, schema extensions, and overrides to alter behavior. Direct core modifications will make upgrades extremely difficult and are unnecessary given the extensibility provided.
* **Leverage Hooks First:** Before considering a template override or a post-controller, see if there’s a hook. Hooks are more granular and often safer. Use PHP hooks to adjust logic and TPL hooks to adjust display. Only override a template if you need to change the structure in a way hooks can’t.
* **Keep Add-on Scope Focused:** One add-on should ideally encapsulate one feature or a set of related features. This makes it easier to enable/disable without side effects. It also makes the code easier to navigate. CS-Cart’s own add-ons (e.g., “SEO”, “Wishlist”) are good examples to study.
* **Follow Naming Conventions:** Maintain consistent naming – e.g., if your add-on is named `my_feature`, prefix all your functions with `fn_my_feature_`, your hooks with `my_feature:` or file names with `my_feature_`. This avoids collisions. Also, for language variables, prefix them with your addon id: e.g., `my_feature.example_text` for a language var. This prevents any naming conflicts with other add-ons or core.
* **Clean Installation/Uninstallation:** Ensure that installing your add-on doesn’t break anything and uninstalling cleans up. Use `<queries>` and `<functions>` in addon.xml wisely to set up and tear down. If you create files or folders (unlikely, but say you generate images or data files), consider removing them on uninstall (perhaps in a `fn_myaddon_uninstall()` called via `<functions for="uninstall">`).
* **Performance Considerations:** Hooks and add-ons do add overhead. Minimize what your hooks do, especially in frequently triggered hooks (like those in checkout or product listing loops). Avoid heavy SQL in hooks that run often; cache results or use static caching inside your function. CS-Cart is used in high-traffic stores, and badly optimized add-ons can become bottlenecks.
* **Security:** Use CS-Cart’s filtering and validation. For example, use `fn_input_filter` for user inputs, rely on built-in CSRF token checks (forms in CS-Cart use hidden token fields and the core checks them; if you make a custom form, include `{form hidden fields}` Smarty snippet). Sanitize output or better, let Smarty auto-sanitize (escape) unless you have to output raw HTML.
* **Testing in Different Modes:** Test your add-on in **both admin and storefront**, and if Multi-Vendor, test as vendor and admin. Different roles might trigger different code paths (permission schema might block something, etc.). Also test with multiple languages (if you have translations) and multiple storefronts if applicable.
* **Respect Multi-Vendor Isolation:** If developing for Multi-Vendor, consider that vendors should usually not affect each other’s data. CS-Cart uses the `company_id` field in many tables to segregate vendor data. If your add-on introduces new data, decide if it’s per-vendor (add a `company_id` field and filter accordingly) or global. Also use `fn_get_company_condition` helper in queries if needed to restrict data by vendor.
* **User Interface Consistency:** If adding UI elements, try to follow CS-Cart’s UI style (use existing CSS classes, forms structure, buttons). For admin templates, you can use pre-made components like the ones in `design/backend/templates/common/` (there are common templates for forms, pickers, etc.). A consistent UI makes your add-on feel native.
* **Documentation and agents.md:** Document your code and structure. The `agents.md` (like this document) is helpful for AI or new devs to understand the design. Also, comment in code, especially in tricky hook logic: e.g., “// Hook into checkout to apply reward points discount”.
* **Keep Up with CS-Cart Changes:** CS-Cart evolves (for instance, the new Notification system, or changes in hooks between versions). Keep an eye on the **Version History** in docs or release notes to update your add-ons. E.g., if in 4.14 a hook name changed or a new API introduced, adapt to it. The addon.xml compatibility `<core_version>` can enforce version constraints, but ideally make your add-on forward-compatible.
* **Testing and Debugging Tools:** Use the built-in Developer tools: CS-Cart has a debug mode and a built-in **Debugger** panel that can show template trace, SQL queries, hook triggers, etc. Enable developer mode in `config.local.php` (`DEBUG_MODE` constant) and use `fn_print_r()` to dump variables for debugging (only do this in dev, not production).
* **Marketplace Requirements:** If you plan to distribute via CS-Cart Marketplace, adhere to their guidelines. For example, unique addon IDs, proper use of scheme 3.0, no core file changes, etc. They often review code for compliance.

By adhering to the architecture and best practices outlined above, you can create add-ons that **integrate seamlessly** with CS-Cart. They will survive core upgrades, play well with other add-ons, and provide new features to the platform in a clean, modular way. CS-Cart’s architecture, while complex, provides all the tools needed for safe extensibility—leveraging hooks, controllers, schemas, and the core API will ensure your custom development is robust and maintainable.
