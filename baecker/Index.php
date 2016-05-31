<?php // UTF-8 marker äöüÄÖÜß€
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
require_once './InputElement.php';

class Index extends Page
{
    // to do: declare reference variables for members
    // representing substructures/blocks
    private $header;


    private $orders = array();

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
        $this->header = new Header($this->_database, "Bäcker");
        // to do: instantiate members representing substructures/blocks
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
            $page = new Index();
            $page->processReceivedData();
            $page->generateView();
        } catch (Exception $e) {
            header("Content-type: text/plain; charset=UTF-8");
            echo $e->getMessage();
        }
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
    }

    private function generateHead(){
        return <<<EOT
    <link rel="stylesheet" type="text/css" href="/ewa-pizzaservice/datei.css"/>
    <script src="/ewa-pizzaservice/poll.js" type="application/javascript"></script>
    <script>
        window.startPolling('/ewa-pizzaservice/baecker');
    </script>
EOT;
    }

    private function generatePreTable($address){
        return <<<EOT
    <div class="rahmen">
    <p class="adresse">Bestellung an Adresse: {$address}</p>
    <table>
        <tr>
            <th></th>
            <th>bestellt</th>
            <th>im Ofen</th>
            <th>fertig</th>
        </tr>
EOT;
    }

    private function generatePostTable(){
        return <<<EOT
</table>
</div>
EOT;
    }

    private function generateInfoMessage(){
        return <<<EOT
        <p class="info-message">Momentan müssen Sie nichts backen</p>
EOT;

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

        $html = "";

        $html .= $this->generatePageHeader('Pizzaservice');

        $html .= $this->generateHead();

        $html .= $this->header->generateView();

        if (sizeof($this->orders) > 0){
            foreach ($this->orders as $order){
                $html .= $this->generatePreTable($order['address']);
                foreach ($order['pizzas'] as $pizza) {
                    $id = $pizza['id'];
                    $name = $pizza['pizza_name'];
                    $status = $pizza['status'];
                    $checked = array(
                        0 => $status == 0 ? "checked" : "",
                        1 => $status == 1 ? "checked" : "",
                        2 => $status == 2 ? "checked" : ""
                    );
                    $element = new InputElement($this->_database, $id, $name, $checked);
                    $html .=  $element->generateView();
                }
                $html .= $this->generatePostTable();
            }
        }else{
            $html .= $this->generateInfoMessage();
        }

        $html .= $this->generatePageFooter();
        echo $html;
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
        $rows = $this->_database->query("SELECT * FROM `order` WHERE status = 0 ORDER BY order_time ASC");
        foreach ($rows as $row) {
            $obj = array(
                "id" => $row['id'],
                "address" => $row['address'],
                "full_price" => $row['full_price'],
                "status" => $row['status']);
            $stmt = $this->_database->stmt_init();
            $stmt->prepare("SELECT * FROM ordered_pizza WHERE order_id = ? AND status <= 2");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = array();
            foreach ($result as $item) {
                array_push($items, array("id" => $item["id"], "pizza_name" => $item["pizza_name"], "status" => $item["status"]));
            }
            $obj['pizzas'] = $items;
            array_push($this->orders, $obj);
        }

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

    private function toMoney($number)
    {
        return str_replace(".", ",", number_format($number, 2)) . "€";
    }
}

// This call is starting the creation of the page.
// That is input is processed and output is created.
Index::main();

// Zend standard does not like closing php-tag!
// PHP doesn't require the closing tag (it is assumed when the file ends).
// Not specifying the closing ? >  helps to prevent accidents
// like additional whitespace which will cause session
// initialization to fail ("headers already sent").
//? >