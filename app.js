/**
 * Generated with PhpStorm 10
 * @author Andreas Schattney
 * @author Nada Mahmuzic
*/

"use strict";
/*
    Ids der verwendeten HTML - Elemente
 */
var PIZZA_LIST_ID = 'pizza_list';
var TOTAL_PRICE_ID = 'total_price';
var LIEFERADRESSE_ID = 'lieferadresse';
var FORM_ID = 'form';

/*
    Nachrichten an den Nutzer
 */
var MESSAGE_ADDRESS_MISSING = "Bitte geben Sie eine Lieferadresse ein!";
var MESSAGE_PIZZA_MISSING = "Sie müssen mindestens eine Pizza in den Warenkorb ablegen!";

/**
 * Erzeugt einen neuen Warenkorb
 * @constructor
 */
function Cart() {
    /**
     * Pizzen, die im Warenkorb enthalten sind
     * @type {Array}
     */
    var pizzas = [];

    /**
     * Referenz auf das <b><select></b> Element in <b>Bestellung.html</b>
     * @type {Element}
     */
    var pizzaHtmlSelectElement = document.getElementById(PIZZA_LIST_ID);

    /**
     * Referenz auf das <b><form></b> Element in <b>Bestellung.html</b>
     * @type {Element}
     */
    var form = document.getElementById(FORM_ID);

    /**
     * Referenz auf das <b><p></b> Element in <b>Bestellung.html</b>
     * @type {Element}
     */
    var priceElement = document.getElementById(TOTAL_PRICE_ID);

    /**
     * Referenz auf das <b><input></b> Element in <b>Bestellung.html</b>
     * @type {Element}
     */
    var addressInput = document.getElementById(LIEFERADRESSE_ID);

    /**
     * Fügt eine Pizza mit dem Namen {@code name} und dem Preis {@code price} dem Warenkorb hinzu
     * @param name Name der Pizza
     * @param price Preis der Pizza
     */
    this.addPizza = function (name, price) {

        // Prüfen ob ein Pizza mit dem Namen bereits im Warenkorb vorhanden ist
        var item = findPizzaElementByName(name);

        if (item === null) {
            pizzas.push({name: name, price: price, count: 1}); // Neu hinzufügen, mit Anzahl 1
        } else {
            item.count += 1; // Ansonsten die Anzahl um 1 erhöhen
        }

        render();
    };

    /**
     * Sucht das Pizza Objekt in der Liste anhand des Namens der Pizza
     * @param name Name der Pizza
     * @returns {*} liefert eine Referenz auf das Objekt zurück, falls es gefunden wurde, ansonsten <b>null</b>
     */
    function findPizzaElementByName(name) {
        var i;
        for (i = 0; i < pizzas.length; i+=1) {
            if (pizzas[i].name == name) {
                return pizzas[i];
            }
        }
        return null;
    }

    /**
     * Entfernt die aktuell ausgewählte Pizza aus dem Warenkorb
     */
    this.removePizza = function () {
        var index = pizzaHtmlSelectElement.selectedIndex;
        if (index != -1) {
            var obj = JSON.parse(pizzaHtmlSelectElement.options[index].value);
            var pizza = findPizzaElementByName(obj.name);
            if (pizza.count > 1) {
                pizza.count -= 1;
                render();
                pizzaHtmlSelectElement.options[index].selected = true;
            } else {
                var elemIndex = pizzas.indexOf(pizza);
                pizzas.splice(elemIndex, 1);
                render();
            }
        }
    };

    /**
     * Setzt den Warenkorb zurück.
     */
    this.reset = function () {
        pizzas = [];
        render();
    };

    /**
     * Sendet den Warenkorb an den Server
     * @returns {boolean} {@code true}, wenn die benötigten Daten vollständig angegegeben wurden und die Form gesendet werden konnte, andernfalls {@code false}
     */
    this.submit = function () {
        var text = addressInput.value;

        if (!userHasSelectedAnyPizza()){
            alert(MESSAGE_PIZZA_MISSING);
            return false;
        }

        if (isValidAddress(text)) {
            render(); // Noch einmal rendern um mögliche Änderungen im HTML zurückzusetzen
            selectEachOptionInDom();
            form.submit();
            return true;
        }else{
            alert(MESSAGE_ADDRESS_MISSING);
            return false;
        }
    };

    function userHasSelectedAnyPizza(){
        return pizzas.length > 0;
    }

    function isValidAddress(text){
        return text !== undefined && text !== null && text.trim().length > 0;
    }

    /**
     * Setzt jedes <b><option></b> Element innerhalb des <b><select></b> Elements auf <b>ausgewählt</b>
     */
    function selectEachOptionInDom() {
        var i;
        var option;
        for (i = 0; i < pizzaHtmlSelectElement.options.length; i+=1) {
            option = pizzaHtmlSelectElement.options[i];
            option.selected = true;
        }
    }

    /**
     * Klont ein Javascript Objekt
     * @param obj Gibt das geklonte Objekt zurück
     */
    function cloneElement(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    /**
     * Konvertiert ein Javascript Objekt {@code obj} in einen im JSON Format kodierten String.
     * @param obj das Javascript Objekt
     */
    function objToString(obj) {
        return JSON.stringify(obj);
    }

    /**
     * Aktualisiert das DOM anhand der Daten im Warenkorb
     */
    function render() {
        resetOrderListInDOM();
        var option;
        var position;
        for (position = 0; position < pizzas.length; position+=1) {
            option = createPizzaOptionElement(pizzas[position]);
            pizzaHtmlSelectElement.add(option);
        }
        pizzaHtmlSelectElement.selectedIndex = -1;
        var price = calculatePrice();
        updatePriceInDOM(price);
    }

    /**
     * Erzeugt ein <b><option></b> Element und setzten die Werte anhand des übergebenen {@code pizza} Objekts
     * @param pizza Enthält den <b>Namen</b> der Pizza (pizza<b>.name</b>) und die <b>Anzahl</b> dieser Pizzen im Warenkorb (pizza<b>.count</b>)
     * @returns {Element} option
     */
    function createPizzaOptionElement(pizza){
        // Option Element erzeugen
        var option = document.createElement("option");
        // Informationen zur Pizza als Text angeben
        option.text = pizza.count + "x " + pizza.name;
        // Das Pizza Objekt klonen, damit die nächste Modifikation nicht auf das Element in der Liste angewendet wird
        var obj = cloneElement(pizza);
        // Den Preis aus dem geklonten Objekt entfernen, damit dieser nicht beim Submitten der Form mit übertragen wird
        delete obj.price;
        // Das geklonte Objekt als JSON formatierten String dem Wert des Option Elements zuweisen
        option.value = objToString(obj);
        return option;
    }

    /**
     * Setzt den Preis {@code totalPrice} im DOM
     * @param totalPrice der Preis
     */
    function updatePriceInDOM(totalPrice){
        var priceString = totalPrice + "€";
        /*
         *  textContent ist eigentlich der Standard,
         *  nur der Internet Explorer unterstützt dieses Attribut in älteren Versionen nicht.
         */
        if (priceElement.textContent) {
            priceElement.textContent = priceString;
        }else {
            priceElement.innerText = priceString;
        }
    }

    /**
     * Löscht alle <b><option></b> Elemente aus der <b><select></b>
     */
    function resetOrderListInDOM() {
        // Solange es ein gültiges erstes Kindelement gibt
        while (pizzaHtmlSelectElement.firstChild) {
            // dann entferne genau dieses Element
            pizzaHtmlSelectElement.removeChild(pizzaHtmlSelectElement.firstChild);
        }
    }

    /**
     * Berechnet den Kaufpreis für die Pizzen im Warenkorb
     */
    function calculatePrice() {
        var totalPrice = 0.0;
        var position;
        for (position = 0; position < pizzas.length; position+=1) {
            var pizza = pizzas[position];
            // Anzahl der ausgewählten Pizzen * Preis, z.B. 3x Margherita
            totalPrice += pizza.count * pizza.price;
        }
        totalPrice = totalPrice.toLocaleString('de-GER', {minimumFractionDigits: 2});
        return totalPrice;
    }
}

/**
 * Erzeugt ein neues Warenkorb Objekt, wenn die Seite komplett geladen wurde
 */
window.onload = function () {
    window.cart = new Cart();
};