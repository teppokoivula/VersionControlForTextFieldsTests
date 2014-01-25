<?php

/**
 * PHPUnit tests for Version Control For Text Fields ProcessWire module
 * 
 * Intended to be run against a clean installation of ProcessWire with Version
 * Control For Text Fields installed. Most of the tests included depend on each
 * other, which is why they're grouped together into one file and use depends
 * annotation.
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
     * Static properties shared by all tests; these are part helpers and part
     * requirement set by certain features of ProcessWire.
     *
     */
    protected static $rows;
    protected static $data_rows;
    protected static $page_id;

    /**
     * Executed once before tests
     *
     * Set test environment up by removing old data, bootstrapping ProcessWire
     * and making sure that module undergoing tests is properly installed.
     *
     * After each time the module is installed, we're going to exit and output a
     * message asking to rerun the tests. This is required in order to make sure
     * that all required hook methods are properly seen and run by ProcessWire.
     * 
     */
    public static function setUpBeforeClass() {

        // Bootstrap ProcessWire
        require '../../../index.php';

        // Install and configure module (if possible and not already installed)
        $module = substr(__CLASS__, 0, strlen(__CLASS__)-4);
        if (!wire('modules')->isInstalled($module)) {
            if (wire('modules')->isInstallable($module)) {
                wire('modules')->install($module);
                $data = array(
                    'enabled_templates' => array(
                        29, // basic-page
                    ),
                    'enabled_fields' => array(
                        1, // title
                        76, // body
                    ),
                );
                $defaults = VersionControlForTextFields::getDefaultData();
                $data = array_merge($defaults, $data);
                wire('modules')->saveModuleConfigData($module, $data);
                exit("Module $module installed and configured, please rerun tests.\n");
            } else {
                exit("Module $module not installable, please install manually and rerun tests.\n");
            }
        }

        // Remove any pages created but not removed during tests
        foreach (wire('pages')->find("title^='a test page', include=all") as $page) {
            $page->delete();
        }

        // Setup static variables
        self::$rows = 0;
        self::$data_rows = 0;

    }

    /**
     * Executed once after all tests are finished
     *
     * Cleanup; remove any pages created but not removed during tests (and
     * uninstall the module) in order to prepare this site for new tests.
     *
     */
    public static function tearDownAfterClass() {

        // Remove any pages created but not removed during tests
        foreach (wire('pages')->find("title^='a test page', include=all") as $page) {
            $page->delete();
        }

        // Uninstall module (if installed)
        $module = substr(__CLASS__, 0, strlen(__CLASS__)-4);
        if (wire('modules')->isInstalled($module)) {
            if (wire('modules')->isUninstallable($module)) {
                wire('modules')->uninstall($module);
            } else {
                exit("Module $module not uninstallable, please uninstall manually before any new tests.\n");
            }
        }

    }

    /**
     * Executed after each test
     *
     * At the moment all tests end with same assertions, so it makes sense to
     * move those here wwhere they gets executed automatically after each test.
     *
     */
    public function tearDown() {
        $sql = "SELECT COUNT(*) FROM " . VersionControlForTextFields::TABLE_NAME;
        $row = wire('db')->query($sql)->fetch_row();
        $this->assertEquals(self::$rows, reset($row));
        $sql = "SELECT COUNT(*) FROM " . VersionControlForTextFields::TABLE_NAME;
        $row = wire('db')->query($sql)->fetch_row();
        $this->assertEquals(self::$data_rows, reset($row));
    }

    /**
     * Add new page
     *
     * Only field under version control that we're modifying here is 'title',
     * so we should get one new row in both version control database tables.
     *
     * @return Page
     */
    public function testAddPage() {
        $page = new Page;
        $page->parent = wire('pages')->get('/');
        $page->template = wire('templates')->get('basic-page');
        $page->title = "a test page";
        $page->save();
        self::$page_id = $page->id;
        self::$rows += 1;
        self::$data_rows += 1;
        
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
        $page->title = $page->title . " 2";
        $page->body = "body text";
        $page->save();
        self::$rows += 2;
        self::$data_rows += 2;
        
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
        self::$rows += 2;
        self::$data_rows += 2;

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

        return $page;

    }

    /**
     * Make sure that database contains correct data
     *
     * In order to compare database rows more easily, we'll encode them as JSON
     * and make the data provider method also give us JSON, one row at a time.
     *
     * @dataProvider providerForTestTableData
     * @param int $key ID number of current database table row
     * @param string $data Expected database table row data
     */
    public function testTableData($key, $data) {

        // "?" is used as placeholder for page ID in the data provided by data provider
        if (strpos($data, "?") !== false) $data = str_replace("?", self::$page_id, $data);

        // Table names
        $t1 = VersionControlForTextFields::TABLE_NAME;
        $t2 = VersionControlForTextFields::DATA_TABLE_NAME;

        $sql = "
        SELECT pages_id, fields_id, users_id, username, property, data 
        FROM {$t1} t1
        JOIN {$t2} t2 ON t2.{$t1}_id = t1.id 
        LIMIT {$key},1
        ";
        $row = wire('db')->query($sql)->fetch_row();

        // Make sure that row of data from database matches expected data provided by data provider
        $this->assertEquals($data, json_encode($row));

    }

    /**
     * Data provider for testTableData
     * 
     * Question mark ("?") is a placeholder for page ID, which in ProcessWire is
     * dynamic; since we can't use our static self::$page_id variable here we'll
     * just have to replace it once the row is provided to testTableData method.
     *
     * @return array Array of expected database rows, each an array of it's own
     */
    public static function providerForTestTableData() {
        return array(
            array(0, '["?","1","40","guest","data","a test page"]'),
            array(1, '["?","1","40","guest","data","a test page 2"]'),
            array(2, '["?","76","40","guest","data","body text"]'),
            array(3, '["?","1","40","guest","data","a test page 3"]'),
        );
    }

    /**
     * Delete previously added page
     *
     * This operation should clear all previously added rows from version
     * control database table.
     *
     * @depends testRestorePage
     * @param Page $page
     */
    public function testDeletePage(Page $page) {
        $page->delete();
        self::$rows = 0;
        self::$data_rows = 0;
    }

    /**
     * Create another page with a template NOT under version control
     *
     * Since this page isn't under version control, no rows should be saved into
     * version control database tables.
     *
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

}