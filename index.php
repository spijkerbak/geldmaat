<?php
// show errors in browser during development
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

// test data
class Test
{
    static $accounts;
    static $pin;
    static $iban;
    static $amount;
}
Test::$accounts = [
    "NL10BANK100001" => ["pin" => 1234, "balance" => 100],
    "NL10BANK100002" => ["pin" => 2345, "balance" => 2000],
    "NL10BANK100003" => ["pin" => 3456, "balance" => 30000],
];
Test::$pin = filter_input(INPUT_POST, 'pin') ?? 0;
Test::$iban = filter_input(INPUT_POST, 'iban') ?? "";
Test::$amount = filter_input(INPUT_POST, 'amount') ?? 0;

class GUI
{
    function readPin()
    {
        return Test::$pin;
    }
    function readAmount(): int
    {
        return Test::$amount;
    }
}

class CardReader
{
    function checkPin($pin): bool
    {
        $iban = Test::$iban;
        if (!isset(Test::$accounts[$iban]))
            return false;
        return Test::$accounts[$iban]["pin"] == $pin;
    }

    function getIban(): string
    {
        return Test::$iban;
    }
}

class CashCassette
{
    function checkAmount($amount): bool
    {
        return true;
    }
    function issueAmount($amount): bool
    {
        return true;
    }
}

class Bank
{
    function checkAmount($iban, $amount): bool
    {
        if (!isset(Test::$accounts[$iban]))
            return false;
        return Test::$accounts[$iban]["balance"] >= $amount;
    }

    function withdraw($iban, $amount)
    {
        if (!isset(Test::$accounts[$iban]))
            return "Onbekende IBAN: $iban<br>";
        else
            return "$amount schmeckels opgenomen van $iban<br>";
    }

}

class Controller
{
    private $gui;
    private $reader;
    private $cassette;
    private $bank;

    function __construct()
    {
        $this->gui = new GUI;
        $this->reader = new CardReader;
        $this->cassette = new CashCassette;
        $this->bank = new Bank;
    }
    function run(): string
    {
        $pin = $this->gui->readPin();
        $ok = $this->reader->checkPin($pin);
        if (!$ok)
            return "Pin is fout";

        $amount = $this->gui->readAmount();
        $ok = $this->cassette->checkAmount($amount);
        if (!$ok)
            return "Niet genoeg geld in cassette";

        if ($amount > 1000)
            return "Maximum is 1000 schmeckels";

        $iban = $this->reader->getIban();
        $ok = $this->bank->checkAmount($iban, $amount);
        if (!$ok)
            return "Niet genoeg geld op rekening";


        $this->cassette->issueAmount($amount);

        return $this->bank->withdraw($iban, $amount);

    }
}

$controller = new Controller;

?>
<!doctype html>
<html lang="nl">

<head>
    <title>Geldmaat</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link type="text/css" rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
        }

        label,
        button {
            display: block;
            width: 120px;
            margin: 4px 0 10px 0;
        }
    </style>
</head>

<body>
    <h2>Test data</h2>
    <table>
        <tr>
            <td>IBAN</td>
            <td>Pin</td>
            <td>Saldo</td>
        </tr>
        <?php
        foreach (Test::$accounts as $iban => $data) {
            echo "<tr><td>$iban</td><td>{$data['pin']}</td><td>{$data['balance']}</td></tr>";
        }
        ?>
    </table>

    <h2>Input</h2>
    <form method="POST">
        <label>IBAN<input type="text" name="iban" value=<?= Test::$iban ?>></label>
        <label>Pin<input type="password" name="pin" value=<?= Test::$pin ?>></label>
        <label>Bedrag<input type="number" name="amount" value=<?= Test::$amount ?>></label>
        <button type="submit">Geef geld</button>
    </form>

    <p>
        <?php echo $controller->run(); ?>
    </p>

    <p>
        <a target="class-diagram" href="class-diagram.svg"><img height="100" src="class-diagram.svg" alt="class diagram"
                title="class diagram"></a>
        <a target="github" href="https://github.com/spijkerbak/geldmaat"><img src="github-mark.svg" height="100" alt="sources op github"
                title="sources op github"></a>
    </p>
</body>

</html>