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
require_once '../../template/Page.php';
require_once '../../Header.php';

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
class Index extends Page
{
    // to do: declare reference variables for members
    // representing substructures/blocks

    private $header;
    private $pizzas;
    private $data = array();
    private $address;
    private $full_price;

    private $order_id = 0;

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
        $this->header = new Header($this->_database, "Zusammenfassung der Bestellung");
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


    private function to_object($array) {
        foreach ($array as $key=>$value)
            if (is_array($value))
                $array[$key] = $this->to_object($value);
        return (object)$array;
    }

    private function query($query, $param_types, $params)
    {
        $stmt = $this->_database->stmt_init();
        $stmt->prepare($query);
        $stmt->bind_param($param_types, $params);
        $stmt->execute();
        $arr = $stmt->get_result();
        $result = array();
        while ($row = $arr->fetch_assoc()) {
            array_push($result, $row);
        }
        $stmt->close();
        return $result;
    }

    private function queryOne($query, $param_types, $params)
    {
        $stmt = $this->_database->stmt_init();
        $stmt->prepare($query);
        $stmt->bind_param($param_types, $params);
        $stmt->execute();
        $result = $stmt->get_result();
        $result = $result->fetch_row();
        $stmt->close();
        return $result;
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

        if (isset($_GET['id'])) {



            $query = "SELECT o.pizza_name AS name, COUNT(*) AS `count`, p.price FROM offer o JOIN ordered_pizza p ON o.pizza_name = p.pizza_name JOIN `order` ord ON p.order_id = ord.id WHERE p.order_id = ? GROUP BY o.pizza_name";
            $result = $this->query("{$query}", "i", $_GET['id']);
            $set = $this->queryOne("SELECT address, full_price FROM `order` WHERE id = ?", "i", $_GET['id']);

            if (sizeof($set) == 0){
                return false;
            }

            $this->address = $set[0];
            $this->full_price = $set[1];
            $this->full_price = $this->toMoney((double)$this->full_price) . "€";
            foreach($result as &$row){
                $price = $row['price'] * $row['count'];
                $row['money'] = $this->toMoney($price);
            }
            $this->data = $this->to_object($result);

        } else {

            foreach ($this->pizzas as $pizza) {
                $pizza = json_decode($pizza);
                $stmt = $this->_database->stmt_init();
                $stmt->prepare("SELECT price FROM offer WHERE pizza_name = ?");
                $stmt->bind_param("s", $pizza->name);
                $stmt->execute();
                $price = 0.0;
                $stmt->bind_result($price);
                $stmt->fetch();
                $pizza->price = $price;
                $pizza->money = $this->toMoney($price * $pizza->count);
                $this->full_price += $price * $pizza->count;
                array_push($this->data, $pizza);
            }

            $p = $this->full_price;
            $this->full_price = $this->toMoney($this->full_price) . "€";

            $stmt = $this->_database->stmt_init();
            $stmt->prepare("INSERT INTO `order` (address, full_price, status) VALUES(?,?,0)");
            $stmt->bind_param("sd", $this->address, $p);
            $stmt->execute();

            $this->order_id = $stmt->insert_id;

            array_push($_SESSION['order_ids'], $this->order_id);

            foreach ($this->data as $pizza) {
                for ($i = 0; $i < $pizza->count; $i++) {
                    $stmt = $this->_database->stmt_init();
                    $stmt->prepare("INSERT INTO ordered_pizza (order_id, pizza_name, price, status) VALUES(?, ?, ?, ?)");
                    $status = "0";
                    $stmt->bind_param("isds", $this->order_id, $pizza->name, $pizza->price , $status);
                    $stmt->execute();
                }
            }
        }
        return true;
    }

    private function toMoney($number)
    {
        return str_replace(".", ",", number_format($number, 2));
    }

    private function generatePreBlock(){
        return <<<EOT
        <div class='block-padding-small'>
        <h2>Bestellung: {$this->order_id}</h2>
        <table class='table'>
        <tr>
        <th>Anzahl</th>
        <th>Pizza</th>
        <th>Preis</th>
        </tr>
        </div>
EOT;
    }

    private function generateRow($row){
        return <<<EOT
            <tr>
            <td>{$row->count}x</td>
            <td>{$row->name}</td>
            <td>{$row->money}€</td>
            </tr>
EOT;

    }

    private function generatePostBlock(){
        return <<<EOT
        </table>
        <p>Gesamtpreis: <span class='price'>{$this->full_price}</span></p>
        <p>Adresse: {$this->address}</p>
EOT;

    }

    private function generateHead(){
        return <<<EOT
        <link rel="stylesheet" type="text/css" href="/ewa-pizzaservice/datei.css"/>
EOT;

    }

    private function generateInfoMessageOrderNotFound(){
        return <<<EOT
        <p class="info-message">Eine Bestellung mit der Nummer {$this->order_id} existiert nicht</p>
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

        $html = "";

        $html = $this->generatePageHeader('Pizzaservice');
        $html .= $this->generateHead();
        $html .= $this->header->generateView();

        if ($this->getViewData()){
            $html .=  $this->generatePreBlock();
            foreach ($this->data as $row) {
                $html .= $this->generateRow($row);
            }
            $html .= $this->generatePostBlock();
            $html .= $this->generatePageFooter();
        }else{
            $html .= $this->generateInfoMessageOrderNotFound();
        }
        echo $html;
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

        if (isset($_GET['id'])) {
            $this->order_id = $_GET['id'];
        } else {
            if (!isset($_POST['pizzas'])) {
                header("Location: /ewa-pizzaservice/bestellung");
                return false;
            } else if (!isset($_POST['lieferadresse'])) {
                header("Location: /ewa-pizzaservice/bestellung");
                return false;
            }
            $this->pizzas = $_POST['pizzas'];
            $this->address = $_POST['lieferadresse'];

            session_start();

            if (!isset($_SESSION['order_ids']))
                $_SESSION['order_ids'] = array();
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
            $page = new Index();
            if ($page->processReceivedData())
                $page->generateView();
        } catch (Exception $e) {
            header("Content-type: text/plain; charset=UTF-8");
            echo $e->getMessage();
        }
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