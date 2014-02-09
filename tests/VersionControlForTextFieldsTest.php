<?php

/**
 * PHPUnit tests for Version Control For Text Fields ProcessWire module
 * 
 * Intended to be run against a clean installation of ProcessWire with Version
 * Control For Text Fields available. Most of the tests depend on each other,
 * so they're grouped together into one file and use depends annotation.
 *
 * DO NOT run these tests against production site, as they will add, edit and
 * remove pages when necessary, thus potentially seriously damaging your site!
 * 
 * @author Teppo Koivula, <teppo@flamingruby.com>
 * @copyright Copyright (c) 2014, Teppo Koivula
 * @license GNU/GPL v2, see LICENSE
 */
class VersionControlForTextFieldsTest extends PHPUnit_Framework_TestCase {

    /**
     * Static properties shared by all tests
     *
     */
    protected static $data;
    protected static $module_name;

    /**
     * Executed once before tests
     *
     * Set test environment up by removing old data, bootstrapping ProcessWire
     * and making sure that module undergoing tests is uninstalled.
     *
     */
    public static function setUpBeforeClass() {

        // Bootstrap ProcessWire
        require '../../../index.php';

        // Messages and errors
        $messages = array();
        $errors = array();

        // Set module name
        self::$module_name = substr(__CLASS__, 0, strlen(__CLASS__)-4);

        // Create new page field and add it to basic-page template (unless this
        // field already exists and has been added to said template)
        $field = wire('fields')->get('page');
        if (!$field->id) {
            $field = new Field;
            $field->type = wire('modules')->get('FieldtypePage');
            $field->name = 'page';
            $field->parent_id = 1; // home
            $field->inputfield = 'InputfieldAsmSelect';
            $field->save();
            $messages[] = substr($field->type, 9) . " field '{$field->name}' added.";
        }
        $fieldgroup = wire('fieldgroups')->get('basic-page');
        if (!$fieldgroup->hasField($field)) {
            $fieldgroup->add($field);
            $fieldgroup->save();
            $messages[] = substr($field->type, 9) . " field '{$field->name}' added to fieldgroup '{$fieldgroup->name}'.";
        }

        // Create new repeater field and add it to basic-page template (unless
        // this field already exists and has been added to said template)
        $field_name = "repeater";
        $field = wire('fields')->get($field_name);
        if (!$field->id) {
            $fieldgroup = new Fieldgroup;
            $fieldgroup->name = "repeater_" . $field_name;
            $fieldgroup->append(wire('fields')->get('title'));
            $fieldgroup->save();
            $template = new Template;
            $template->name = "repeater_" . $field_name;
            $template->fieldgroup = $fieldgroup;
            $template->flags = Template::flagSystem;	
            $template->noChildren = 1; 
            $template->noParents = 1;
            $template->noGlobal = 1; 
            $template->save();
            $field = new Field;
            $field->type = wire('modules')->get('FieldtypeRepeater');
            $field->name = $field_name;
            $field->parent_id = wire('pages')->get("name=for-field-{$field->id}")->id;
            $field->template_id = $template->id;
            $field->save();
            $messages[] = substr($field->type, 9) . " field '{$field->name}' added.";
        }
        $fieldgroup = wire('fieldgroups')->get('basic-page');
        if (!$fieldgroup->hasField($field)) {
            $fieldgroup->add($field);
            $fieldgroup->save();
            $messages[] = substr($field->type, 9) . " field '{$field->name}' added to fieldgroup '{$fieldgroup->name}'.";
        }

        // Remove any pages created but not removed during previous tests
        foreach (wire('pages')->find("name^=a-test-page, include=all") as $page) {
            $page->delete();
            $messages[] = get_class($page) . " '{$page->url}' deleted.";
        }

        // Uninstall module (if installed)
        if (wire('modules')->isInstalled(self::$module_name)) {
            if (wire('modules')->isUninstallable(self::$module_name)) {
                wire('modules')->uninstall(self::$module_name);
                $messages[] = "Module '" . self::$module_name . "' uninstalled.";
            } else {
                $errors[] = "Module '" . self::$module_name . "' not uninstallable, please uninstall manually before any new tests.";
            }
        }

        // Messages and errors
        if ($messages) echo "\n" . implode($messages, " ") . "\n\n";
        if ($errors) die("\n" . implode($errors, " ") . "\n\n");
        
        // Setup static variables
        self::$data = array();

    }

    /**
     * Executed once after all tests are finished
     *
     * Cleanup; remove any pages created but not removed during tests (and
     * uninstall the module) in order to prepare this site for new tests.
     *
     */
    public static function tearDownAfterClass() {

        // Messages and errors
        $messages = array();
        $errors = array();

        // Remove any pages created but not removed during tests
        foreach (wire('pages')->find("title^='a test page', include=all") as $page) {
            $page->delete();
            $messages[] = get_class($page) . " '{$page->url}' deleted.";
        }

        // Remove page field from templates (or fieldgroups) it's added to and
        // then remove the field itself.
        $field = wire('fields')->get('page');
        if ($field->id) {
            $fieldgroups = $field->getFieldgroups();
            foreach ($fieldgroups as $fieldgroup) {
                $fieldgroup->remove($field);
                $fieldgroup->save();
                $messages[] = substr($field->type, 9) . " field '{$field->name}' removed from fieldgroup '{$fieldgroup->name}'.";
            }
            wire('fields')->delete($field);
            $messages[] = substr($field->type, 9) . " field '{$field->name}' deleted.";
        }

        // Remove repeater field from templates (or fieldgroups) it's added to
        // and then remove the field itself.
        $field = wire('fields')->get('repeater');
        if ($field->id) {
            $fieldgroups = $field->getFieldgroups();
            foreach ($fieldgroups as $fieldgroup) {
                $fieldgroup->remove($field);
                $fieldgroup->save();
                $messages[] = substr($field->type, 9) . " field '{$field->name}' removed from fieldgroup '{$fieldgroup->name}'.";
            }
            foreach (wire('pages')->find("template=repeater_{$field->name}, include=all") as $page) {
                $page->delete();
                $messages[] = get_class($page) . " '{$page->url}' deleted.";
            }
            wire('fields')->delete($field);
            $messages[] = substr($field->type, 9) . " field '{$field->name}' deleted.";
        }

        // Uninstall module (if installed)
        if (wire('modules')->isInstalled(self::$module_name)) {
            if (wire('modules')->isUninstallable(self::$module_name)) {
                wire('modules')->uninstall(self::$module_name);
                $messages[] = "Module '" . self::$module_name . "' uninstalled.";
            } else {
                $errors[] = "Module '" . self::$module_name . "' not uninstallable, please uninstall manually before any new tests.";
            }
        }

        // Messages and errors
        if ($messages) echo "\n\n" . implode($messages, " ") . "\n";
        if ($errors) die("\n\n" . implode($errors, " ") . "\n");
        
    }

    /**
     * Executed after each test
     *
     * Almost all tests end with same assertions, so it makes sense to move
     * those here where they get executed automatically after each test.
     *
     */
    public function tearDown() {
        if (!in_array($this->getName(), array("testModuleIsInstallable", "testUninstallModule"))) {
            $t1 = constant(self::$module_name . "::TABLE_NAME");
            $t2 = constant(self::$module_name . "::DATA_TABLE_NAME");
            $result = wire('db')->query("
            SELECT pages_id, fields_id, users_id, username, property, data 
            FROM {$t1} t1
            JOIN {$t2} t2 ON t2.{$t1}_id = t1.id 
            ");
            $num = 0;
            while ($row = $result->fetch_row()) {
                $data = self::$data[$num];
                $message = null;
                if (!$data) {
                    $data = array();
                    $message = "Local data row was NULL, using placeholder array to get meaningful diff output.";
                }
                $this->assertEquals($data, $row, $message);
                ++$num;
            }
        }
    }
    
    /**
     * Make sure that module is installable
     *
     * @return string module name
     */
    public function testModuleIsInstallable() {
        $this->assertTrue(wire('modules')->isInstallable(self::$module_name));
        return self::$module_name;
    }

    /**
     * Install module
     * 
     * @depends testModuleIsInstallable
     * @param string $module_name
     * @return string module name
     */
    public function testInstallModule($module_name) {
        
        // Install the module
        wire('modules')->install($module_name);
        $this->assertTrue(wire('modules')->isInstalled($module_name));
        
        // Configure the module
        $data = array(
            'enabled_templates' => array(
                29, // basic-page
            ),
            'enabled_fields' => array(
                1, // title
                76, // body
                wire('fields')->get('page')->id,
            ),
        );
        $defaults = VersionControlForTextFields::getDefaultData();
        $data = array_merge($defaults, $data);
        wire('modules')->saveModuleConfigData($module_name, $data);
        wire('modules')->triggerInit();

        return $module_name;

    }

    /**
     * Add new page
     *
     * Only field under version control that we're modifying here is 'title',
     * so we should get one new row in both version control database tables.
     *
     * @depends testInstallModule
     * @return Page
     */
    public function testAddPage() {
        $page = new Page;
        $page->parent = wire('pages')->get('/');
        $template = wire('templates')->get('basic-page');
        $page->template = wire('templates')->get('basic-page');
        $page->title = "a test page";
        $page->save();
        self::$data[] = array((string) $page->id, "1", "40", "guest", "data", "a test page");
        return $page;
    }

    /**
     * Make a change to previously added page
     *
     * Both 'title' and 'body' are fields tracked by version control module,
     * so we should get two new rows in both version control database tables.
     * 
     * @depends testAddPage
     * @param Page $page
     * @return Page
     */
    public function testEditPage(Page $page) {
        $page->setOutputFormatting(false);
        $page->title = "a test page 2";
        $page->body = "body text";
        $page->save();
        self::$data[] = array((string) $page->id, "1", "40", "guest", "data", "a test page 2");
        self::$data[] = array((string) $page->id, "76", "40", "guest", "data", "body text");
        return $page;
    }

    /**
     * Unpublish previously added page
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     *
     * @depends testEditPage
     * @param Page $page
     * @return Page
     */
    public function testUnpublishPage(Page $page) {
        $page->setOutputFormatting(false);
        $page->addStatus(Page::statusUnpublished);
        $page->save();
        return $page;
    }

    /**
     * Publish previously added page
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     *
     * @depends testUnpublishPage
     * @param Page $page
     * @return Page
     */
    public function testPublishPage(Page $page) {
        $page->setOutputFormatting(false);
        $page->removeStatus(Page::statusUnpublished);
        $page->save();
        return $page;
    }

    /**
     * Edit and unpublish previously added page
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     *
     * @depends testPublishPage
     * @param Page $page
     * @return Page
     */
    public function testEditAndUnpublishPage(Page $page) {
        $page->setOutputFormatting(false);
        $page->addStatus(Page::statusUnpublished);
        $page->sidebar = "sidebar test";
        $page->save();
        return $page;
    }

    /**
     * Move previously added page 
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     *
     * @depends testEditAndUnpublishPage
     * @param Page $page
     * @return Page
     */
    public function testMovePage(Page $page) {
        $page->setOutputFormatting(false);
        $page->parent = wire('pages')->get("/")->child();
        $page->save();
        return $page;
    }

    /**
     * Trash previously added page
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     * 
     * @depends testMovePage
     * @param Page $page
     * @return Page
     */
    public function testTrashPage(Page $page) {
        $page->setOutputFormatting(false);
        $page->trash();
        return $page;
    }

    /**
     * Restore previously trashed page
     *
     * Since no fields under version control are affected, this shouldn't add
     * any rows to version control database tables.
     *
     * @depends testTrashPage
     * @param Page $page
     * @return Page
     */
    public function testRestorePage(Page $page) {
        $page->setOutputFormatting(false);
        $page->parent = wire('pages')->get("/");
        $page->save();
        return $page;
    }

    /**
     * Fetch a snapshot for page
     *
     * To test snapshots properly, we need to make sure that there's enough time
     * between previous changes and current state, which is why we'll use sleep
     * function. Back and forth testing is mostly just a precaution.
     *
     * @depends testRestorePage
     * @param Page $page
     * @return Page
     */
    public function testSnapshot(Page $page) {

        // Snapshots are based on time and API operations happen very fast, so
        // we'll generate small gap here by making PHP sleep for a few seconds
        sleep(4);

        $page->setOutputFormatting(false);
        $page->title = "a test page 3";
        $page->name = $page->title;
        $page->body = "new body text";
        $page->save();
        self::$data[] = array((string) $page->id, "1", "40", "guest", "data", "a test page 3");
        self::$data[] = array((string) $page->id, "76", "40", "guest", "data", "new body text");

        $page->snapshot('-2 seconds');
        $this->assertEquals('a test page 2', $page->title);
        $this->assertEquals('body text', $page->body);

        $page->snapshot();
        $this->assertEquals('a test page 3', $page->title);
        $this->assertEquals('new body text', $page->body);

        $page->title = "a test page 4";

        $page->snapshot('-2 seconds');
        $this->assertEquals('a test page 2', $page->title);
        $this->assertEquals('body text', $page->body);

        // Reset page
        $page = wire('pages')->get($page->id);

        return $page;

    }

    /**
     * Edit page type field
     *
     * This should add one row to both version history database tables.
     *
     * @depends testSnapshot
     * @param Page $page
     * @return Page
     */
    public function testEditPageField(Page $page) {
        $page->setOutputFormatting(false);
        $page->page = wire('pages')->get('/about/');
        $page->save();
        // @todo find out why another $page->save() here would save duplicate data row
        self::$data[] = array((string) $page->id, (string) wire('fields')->get('page')->id, "40", "guest", "data", "1001");
        return $page;
    }

    /**
     * Edit repeater item
     *
     * One row should be added to both version history database tables. Note
     * that pages_id for version_control_for_text_fields row should point to
     * the repeater item (RepeaterPage), not to the page it's on.
     *
     * @depends testEditPageField
     * @param Page $page
     */
    public function testEditRepeaterField(Page $page) {
        $item = $page->repeater->getNew();
        $item->title = "repeater title";
        $page->save('repeater');
        self::$data[] = array((string) $item->id, "1", "40", "guest", "data", "repeater title");
    }

    /**
     * Delete previously added page
     *
     * This operation should clear all previously added rows from version
     * control database table.
     *
     * Note: this will fail unless you're running ProcessWire 2.4+ with issue
     * related to repeater garbage cleaning fixed in Pages->delete().
     *
     * @depends testRestorePage
     * @param Page $page
     */
    public function testDeletePage(Page $page) {
        $page->delete();
        self::$data = array();
    }

    /**
     * Create another page with a template NOT under version control
     *
     * Since this page isn't under version control, no rows should be saved into
     * version control database tables.
     *
     * @depends testInstallModule
     * @return Page
     */
    public function testAddNonVersionedPage() {
        $page = new Page;
        $page->parent = wire('pages')->get('/');
        $page->template = wire('templates')->get('sitemap');
        $page->title = "a test page 5";
        $page->save();
        return $page;
    }

    /**
     * Edit previously created non-versioned page
     *
     * Just like previous test, no rows should appear into version control
     * database tables.
     * 
     * @depends testAddNonVersionedPage
     * @param Page $page
     * @return Page
     */
    public function testEditNonVersionedPage(Page $page) {
        $page->title = "a test page 6";
        $page->summary = "summary text";
        $page->save();
        return $page;
    }

    /**
     * Attempt to return non-versioned page to earlier version
     *
     * Since this page isn't under version control, snapshot should not affect
     * page in any way.
     *
     * @depends testAddNonVersionedPage
     * @param Page $page
     * @return Page
     */
    public function testSnapshotOnNonVersionedPage(Page $page) {

        // Snapshots are based on time and API operations happen very fast, so
        // we'll generate small gap here by making PHP sleep for a few seconds
        sleep(4);

        $page->setOutputFormatting(false);
        $page->title = "a test page 3";
        $page->name = $page->title;
        $page->summary = "new summary text";
        $page->save();

        $page->snapshot('-2 seconds');
        $this->assertEquals('a test page 3', $page->title);
        $this->assertEquals('new summary text', $page->summary);

        $page->snapshot();
        $this->assertEquals('a test page 3', $page->title);
        $this->assertEquals('new summary text', $page->summary);

        $page->title = "a test page 4";
        $page->snapshot('-2 seconds');
        $this->assertEquals('a test page 4', $page->title);
        $this->assertEquals('new summary text', $page->summary);

        return $page;

    }

    /**
     * Delete previously created non-versioned page
     *
     * Since this page isn't under version control, once again nothing should
     * change in version control database tables.
     *
     * @depends testSnapshotOnNonVersionedPage
     * @param Page $page
     */
    public function testDeleteNonVersionedPage(Page $page) {
        $page->delete();
    }

    /**
     * Make sure that module is uninstallable
     *
     * @depends testInstallModule
     * @param string $module_name
     * @return string module name
     */
    public function testModuleIsUninstallable($module_name) {
        $this->assertTrue(wire('modules')->isUninstallable($module_name), "This isn't necessarily a sign of real error, as support for installing and uninstalling modules during one request is a PW 2.4+ feature");
        return $module_name;
    }

    /**
     * Uninstall module
     *
     * @depends testModuleIsUninstallable
     * @param string $module_name
     */
    public function testUninstallModule($module_name) {
        wire('modules')->uninstall($module_name);
        $this->assertFalse(wire('modules')->isInstalled($module_name));
    }

}