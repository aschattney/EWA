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
        $this->header = new Header($this->_database, "Kunde");
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
            session_start();
            if (!isset($_SESSION['order_ids']))
                $_SESSION['order_ids'] = array();
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

    private function generatePreOrderBlock($address){
        return <<<EOT
            <div class="rahmen">
            <p class="adresse">Bestellung an Adresse: $address</p>
EOT;
    }

    private function generateTableHeader(){
        return <<<EOT
            <table>
                <tr>
                    <th></th>
                    <th>bestellt</th>
                    <th>im Ofen</th>
                    <th>fertig</th>
                    <th>unterwegs</th>
                </tr>
EOT;

    }

    private function generateRow($name, $checked){
        return <<<EOT
                    <tr>
                    <td>$name</td>
                    <td><input onclick="return false;" type="radio" {$checked[0]}/></td>
                    <td><input onclick="return false;" type="radio" {$checked[1]}/></td>
                    <td><input onclick="return false;" type="radio" {$checked[2]}/></td>
                    <td><input onclick="return false;" type="radio" {$checked[3]}/></td>
                    </tr>
EOT;


    }

    private function generateTableFooter(){
        return <<<EOT
            </table>
EOT;

    }

    private function generatePostOrderBlock(){
        return <<<EOT
         </div>
EOT;

    }

    private function generateLogoutButton(){
        return <<<EOT
        <a href="/ewa-pizzaservice/kunde/logout" class="button">Logout</a>
EOT;

    }

    /**
     * @param $html
     * @return string
     */
    protected function renderOrders()
    {
        $html = "";
        foreach ($this->orders as $order) {
            $html .= $this->generatePreOrderBlock(htmlspecialchars($order['address']));
            $html .= $this->generateTableHeader();
            foreach ($order['pizzas'] as $pizza) {
                $name = $pizza['pizza_name'];
                $status = $pizza['status'];
                $html .= $this->generateRow($name, array(
                    $status == 0 ? 'checked' : '',
                    $status == 1 ? 'checked' : '',
                    $status == 2 ? 'checked' : '',
                    $status == 3 ? 'checked' : ''
                ));
            }
            $html .= $this->generateTableFooter();
            $html .= $this->generatePostOrderBlock();
        }
        return $html;
    }

    private function generateInfoMessageNoOrdersMade(){
        return <<<EOT
        <p class="info-message">Sie haben noch keine Bestellungen getätigt</p>
EOT;

    }

    protected function executePollJsScript(){
        return <<<EOT
    <script>
        window.startPolling('/ewa-pizzaservice/kunde');
    </script>
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
        if ($this->getViewData()) {
            $html = "";

            $scripts = array("css" => array(), "js" => array(), "custom" => array());
            array_push($scripts['js'], '/ewa-pizzaservice/poll.js');
            array_push($scripts['custom'], $this->executePollJsScript());
            array_push($scripts['css'], '/ewa-pizzaservice/datei.css');

            $html .= $this->generatePageHeader('Pizzaservice', $scripts);
            $html .= $this->header->generateView();
            if ($this->isAnyOrderAvailable()) {
                $html .= $this->renderOrders();
                if (isset($_SESSION['order_ids']))
                    $html .= $this->generateLogoutButton();
            } else {
                $html .= $this->generateInfoMessageNoOrdersMade();
            }
            $html .= $this->generatePageFooter();
            echo $html;
        }
    }

    /**
     * Fetch all data that is necessary for later output.
     * Data is stored in an easily accessible way e.g. as associative array.
     *
     * @return none
     */
    protected function getViewData()
    {
        if ($this->_database->connect_errno) {
            throw new Exception("MySQL ErrorCode: " . $this->_database->connect_errno);
            return false;
        }

        try {

            $order_ids = $_SESSION['order_ids'];
            if (sizeof($order_ids) > 0) {
                $order_query = $this->generateOrderQuery($order_ids);
                $rows = $this->fetchOrdersFromDatabase($order_query);
                foreach ($rows as $row) {
                    $obj = $this->mapAttributes($row);
                    $result = $this->fetchPizzasFromOrder($row['id']);
                    $items = $this->extractRelevantOrderAttributes($result);
                    $this->addPizzasToEntity($items, $obj);
                }
            }

        }catch(Exception $e){
            echo $e->getMessage();
            return false;
        }

        return true;
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
     * @param $order_ids
     * @return string
     */
    protected function generateOrderQuery($order_ids)
    {
        $where_order_ids = '';
        $size = sizeof($order_ids);
        $index = 0;
        foreach ($order_ids as $order_id) {
            if ($index == 0) {
                $where_order_ids .= "AND (";
            }
            $where_order_ids .= "id = " . $order_id;
            $index++;
            if ($index < $size) {
                $where_order_ids .= " OR ";
            } else {
                $where_order_ids .= ")";
            }
        }
        $order_query = "SELECT * FROM `order` WHERE status < 3 " . $where_order_ids . " ORDER BY order_time DESC";
        return $order_query;
    }

    /**
     * @param $order_query
     * @return bool|mysqli_result
     */
    protected function fetchOrdersFromDatabase($order_query)
    {
        $rows = $this->_database->query($order_query);
        return $rows;
    }

    /**
     * @param $row
     * @return array
     */
    protected function mapAttributes($row)
    {
        $obj = array(
            "id" => $row['id'],
            "address" => $row['address'],
            "full_price" => $row['full_price'],
            "status" => $row['status']);
        return $obj;
    }

    /**
     * @param $row
     * @return bool|mysqli_result
     */
    protected function fetchPizzasFromOrder($order_id)
    {
        $stmt = $this->_database->stmt_init();
        $stmt->prepare("SELECT * FROM ordered_pizza WHERE order_id = ? AND status <= 3");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    /**
     * @param $result
     * @return array of the extracted information about the orders
     */
    protected function extractRelevantOrderAttributes($result)
    {
        $items = array();
        foreach ($result as $item) {
            array_push($items, array("id" => $item["id"], "pizza_name" => $item["pizza_name"], "status" => $item["status"]));
        }
        return $items;
    }

    /**
     * @param $items
     * @param $obj
     * @return mixed
     */
    protected function addPizzasToEntity($items, $obj)
    {
        $obj['pizzas'] = $items;
        array_push($this->orders, $obj);
        return $obj;
    }

    private function toMoney($number)
    {
        return str_replace(".", ",", number_format($number, 2)) . "€";
    }

    /**
     * @return true if any order was made by the user in the current session, false otherwise
     */
    private function isAnyOrderAvailable()
    {
        return sizeof($this->orders) > 0;
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