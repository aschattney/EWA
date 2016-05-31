<?php	// UTF-8 marker äöüÄÖÜß€
/**
 * Class PageTemplate for the exercises of the EWA lecture
 * Demonstrates use of PHP including class and OO.
 * Implements Zend coding standards.
 * Generate documentation with Doxygen or phpdoc
 *
 * PHP Version 5
 *
 * @category File
 * @package  Pizzaservice
 * @author   Bernhard Kreling, <b.kreling@fbi.h-da.de>
 * @author   Ralf Hahn, <ralf.hahn@h-da.de>
 * @license  http://www.h-da.de  none
 * @Release  1.2
 * @link     http://www.fbi.h-da.de
 */

// to do: change name 'PageTemplate' throughout this file
require_once '../template/Page.php';

/**
 * This is a template for top level classes, which represent
 * a complete web page and which are called directly by the user.
 * Usually there will only be a single instance of such a class.
 * The name of the template is supposed
 * to be replaced by the name of the specific HTML page e.g. baker.
 * The order of methods might correspond to the order of thinking
 * during implementation.

 * @author   Bernhard Kreling, <b.kreling@fbi.h-da.de>
 * @author   Ralf Hahn, <ralf.hahn@h-da.de>
 */

require_once '../Header.php';

class Update extends Page
{

    private $order_id;
    private $status;

    // to do: declare reference variables for members
    // representing substructures/blocks
    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So the database connection is established.
     *
     * @return none
     */
    protected function __construct()
    {
        parent::__construct();
        // to do: instantiate members representing substructures/blocks
    }

    /**
     * Cleans up what ever is needed.
     * Calls the destructor of the parent i.e. page class.
     * So the database connection is closed.
     *
     * @return none
     */
    protected function __destruct()
    {
        parent::__destruct();
    }

    /**
     * Fetch all data that is necessary for later output.
     * Data is stored in an easily accessible way e.g. as associative array.
     *
     * @return none
     */
    protected function getViewData()
    {
        // to do: fetch data for this view from the database
        $stmt = $this->_database->stmt_init();
        $stmt->prepare("UPDATE `order` SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $this->status, $this->order_id);
        $stmt->execute();
        $stmt->close();

        switch($this->status){
            case 2:
                $stmt = $this->_database->stmt_init();
                $stmt->prepare("UPDATE `ordered_pizza` SET status = 3 WHERE order_id = ?");
                $stmt->bind_param("i", $this->order_id);
                $stmt->execute();
                $stmt->close();
                break;
            case 3:
                $stmt = $this->_database->stmt_init();
                $stmt->prepare("UPDATE `ordered_pizza` SET status = 4 WHERE order_id = ?");
                $stmt->bind_param("i", $this->order_id);
                $stmt->execute();
                $stmt->close();
                break;
            default:
                break;
        }

    }

    private function toMoney($number)
    {
        return str_replace(".", ",", number_format($number, 2)) . "€";
    }

    /**
     * First the necessary data is fetched and then the HTML is
     * assembled for output. i.e. the header is generated, the content
     * of the page ("view") is inserted and -if avaialable- the content of
     * all views contained is generated.
     * Finally the footer is added.
     *
     * @return none
     */
    protected function generateView()
    {
        $this->getViewData();
    }

    /**
     * Processes the data that comes via GET or POST i.e. CGI.
     * If this page is supposed to do something with submitted
     * data do it here.
     * If the page contains blocks, delegate processing of the
     * respective subsets of data to them.
     *
     * @return none
     */
    protected function processReceivedData()
    {
        parent::processReceivedData();
        // to do: call processReceivedData() for all members
        if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
            http_response_code(500);
            return;
        }

        $this->order_id = $_POST['order_id'];
        $this->status = $_POST['status'];
        if ($this->order_id <= 0){
            http_response_code(500);
            return false;
        }else if($this->status < 1 || $this->status > 3){
            http_response_code(500);
            return false;
        }
        return true;
    }

    /**
     * This main-function has the only purpose to create an instance
     * of the class and to get all the things going.
     * I.e. the operations of the class are called to produce
     * the output of the HTML-file.
     * The name "main" is no keyword for php. It is just used to
     * indicate that function as the central starting point.
     * To make it simpler this is a static function. That is you can simply
     * call it without first creating an instance of the class.
     *
     * @return none
     */
    public static function main()
    {
        try {
            $page = new Update();
            if ($page->processReceivedData()){
                $page->generateView();
                header('Location: /ewa-pizzaservice/fahrer');
            }
        }
        catch (Exception $e) {
            header("Content-type: text/plain; charset=UTF-8");
            echo $e->getMessage();
        }
    }
}

// This call is starting the creation of the page.
// That is input is processed and output is created.
Update::main();

// Zend standard does not like closing php-tag!
// PHP doesn't require the closing tag (it is assumed when the file ends).
// Not specifying the closing ? >  helps to prevent accidents
// like additional whitespace which will cause session
// initialization to fail ("headers already sent").
//? >