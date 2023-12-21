<?php

    namespace WebanUg\NwtuOrders\Controller;

    use Doctrine\DBAL\DBALException;
    use Doctrine\DBAL\Driver\Exception;
    use Symfony\Component\Mime\Address;
    use TYPO3\CMS\Core\Context\Context;
    use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
    use TYPO3\CMS\Core\Mail\MailMessage;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Utility\MailUtility;
    use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

    class OrderController extends ActionController
    {
        public function __construct()
        {
        }

        function orderAction()
        {
            $smtpUser = "backend@nwtu.de";
            $smtpPass = "oZli*854";
            $html = "";

            $datum = date( "d.m.Y", time() );
            $lastyear = date( 'Y', time() ) - 1;

            $preis_11 = 11; // Nachmeldung
            $preis_20 = 22.1; // Aufnahmegebuehr
            $preis_30 = 11.1; // Pruefungsgebuehr
            $preis_neu = 8; // nwtu Ansteckpin
            $preis_70 = 5.5; // Versandkosten


            // Preisupdate beim Jahreswechsel
            $yChange = mktime( 23, 59, 59, 12, 22, 2021 );
            $now = time();
            if ( $now > $yChange )
            {
                $preis_20 = 22.10;
                $preis_30 = 12.10;
            }


            switch ( $_POST['action'] )
            {
                case "send_bestellung":
                    // Eingaben prüfen
                    $err = 0;
                    foreach ( $_POST['req'] as $key => $name )
                    {
                        if ( !$_POST[ $name ] )
                        {
                            $err++;
                            $errFlds[] = $name;
                        }
                    }
                    if ( $err > 0 && is_array( $errFlds ) )
                    {
                        $html .= "<p>FEHLER: Sie haben nicht alle Pflichtfelder ausgefüllt: ";
                        foreach ( $errFlds as $key => $name )
                        {
                            $html .= "<br>" . $name;
                        }
                        $html .= "<br><br><a href='/?id=439'>Zur Bestellung</a>";
                        break;
                    }

                    $item = "";
                    $to = "office@nwtu.de";

                    $from = $_POST['Email'];
                    $subject = "Bestellung über Online-Formular";

                    $header = ( "From: " . $from . "\n" );
                    $header .= ( "Reply-To: " . $from . "\n" );
                    $header .= ( "Return-Path: " . $from . "\n" );
                    $header .= ( "X-Mailer: PHP/" . phpversion() . "\n" );
                    $header .= ( "X-Sender-IP: " . $_SERVER['REMOTE_ADDR'] . "\n" );
                    $header .= ( "Content-type: text/html; charset=\"utf-8\"\r\n" );
                    $header .= ( 'Bcc: erling@weban.de' );

                    $msg = "";
                    foreach ( $_POST as $key => $value )
                    {
                        if ( !is_array( $value ) && $key != "action" )
                        {
                            $msg .= "<br />" . $key . ": " . $value;
                        }
                    }
                    foreach ( $_POST['Menge'] as $key => $menge )
                    {
                        if ( $menge > 0 )
                        {
                            $item .= "<br />" . $menge . "x " . $_POST['Bezeichnung'][ $key ] . " " . $_POST['Nachmeldung_JSM_Jahr'][ $key ] . " | " . $_POST['Preis'][ $key ] . " &euro;";
                        }
                    }

                    $mail = GeneralUtility::makeInstance( MailMessage::class );
                    $mail->setFrom( MailUtility::getSystemFrom() );
                    $mail->to(
                        new Address( $to )
                    );
                    $mail->setReplyTo( $from );
                    $mail->subject( $subject );
                    $mail->html( $item . "<br><br>" . $msg );;
                    $res = $mail->send();
                    if ( $res )
                    {
                        $html = "<p>Vielen Dank für Ihre Bestellung. Wir werden sie schnellstmöglich bearbeiten.</p>";
                    }
                    else
                    {
                        $html = "<p>FEHLER: Es ist ein Problem beim Versenden Ihrer Bestellung aufgetreten. Bitte probieren Sie es noch einmal.</p>";
                    }
                    break;
                default:
                    $html .= $this->showBestellformular( $datum, $lastyear, $preis_11, $preis_20, $preis_30, $preis_neu, $preis_70 );
                    break;
            }

            return $html;
        }

        private function showBestellformular( $datum, $lastyear, $preis_11, $preis_20, $preis_30, $preis_neu, $preis_70 )
        {

            $html = '
<style>
.bestellung {
    width:800px;
	margin:2% auto;
	background:#fdfdfd;
}
.bestellung * {
    font-size:14px;
}
.bestellung td {
    text-align:left;
    vertical-align:middle;
    padding:5px;
}
.bestellung input[type=text] {
    width:100%;
}
.bestellung .yellow {
    background-color:#f9d408;
}
#user_confirm_order {
	float:left;
	margin:0 10px 0 0;
}
</style>

<form id="bestellung" name="bestellung" method="post" action="">
<h2>Bestellungen online</h2>
<table border="1" cellspacing="0" cellpadding="5" class="bestellung">
    <tr>
        <td colspan="2">
            <div style="font-size:11px;">* Pflichtfelder</div>
            Gl&auml;ubiger ID: <input style="width:165px;" type="text" name="Glaeubiger_ID" value="DE64ZZZ00000747851" readonly="readonly" />
        </td>
        <td colspan="3" style="text-align:right;">  
            Datum: <input style="width: 90px;" type="text" name="Datum" value="' . $datum . '" readonly="readonly" /><br />
            Stand: 01.01.2023
        </td>
    </tr>
    <tr>
        <td colspan="2">Vereinsnummer: *</td>
        <td colspan="2">
            <input class="req yellow" type="text" name="Vereinsnummer" value="" required=required />
            <input type="hidden" name="req[]" value="Vereinsnummer" />
        </td>
        <td style="text-align: center;">Mandatsref.-Nr.</td>
    </tr>    
    <tr>
        <td colspan="2">Vereinsname: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="Vereinsname" value="" required=required />
            <input type="hidden" name="req[]" value="Vereinsname" />
        </td>
    </tr>    
    <tr>
        <td colspan="2">Telefon: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="Telefon" value="" required=required />
            <input type="hidden" name="req[]" value="Telefon" />
        </td>
    </tr>    
    <tr>
        <td colspan="2">E-Mail: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="Email" value="" required=required />
            <input type="hidden" name="req[]" value="Email" />
        </td>
    </tr>    
    <tr>
        <th>Pos.</th>
        <th>Bezeichnung</th>
        <th>Menge</th>
        <th>Einzelpreis</th>
        <th>Gesamtpreis</th>
    </tr>
    <!-- ########################### Erste Zeile ######################################################## -->
    <tr>
        <td><input style="text-align: center;" type="text" name="Pos[]" value="11" readonly="readonly" /></td>
        <td>
            Nachmeldung / Jahressichtmarke f&uuml;r das Jahr&nbsp;<input class="yellow" style="width:50px;" maxlength="4" type="text" name="Nachmeldung_JSM_Jahr[]" value="' . $lastyear . '" />
            <input class="yellow" type="hidden" name="Bezeichnung[]" value="Nachmeldung / Jahressichtmarke" />
        </td>
        <td>
            <input id="menge_11" style="text-align: center;" class="yellow menge" type="text" name="Menge[]" value="" />
            <input id="preis_11" type="hidden" value="' . $preis_11 . '" />
        </td>
        <td style="text-align: right;">
            ' . number_format( $preis_11, 2, ",", "." ) . ' &euro;<br />
            <span style="color: #666;">(9,00 &euro; NWTU + 2,00 &euro; DTU)</span>
        </td>
        <td style="text-align: center;">
            <input class="gesamt" style="width:70%;text-align:right;" id="gesamt_11" type="text" name="Preis[]" value="" readonly="readonly" />&nbsp;&euro;
        </td>
    </tr>
    <!-- ########################### Zweite Zeile ######################################################## -->    
    <tr>
        <td><input style="text-align: center;" type="text" name="Pos[]" value="20" readonly="readonly" /></td>
        <td>
            <strong><ins>Aufnahmegeb&uuml;hr:</ins></strong><br />
            Ausweis einschlie&szlig;lich Sichtvermerk f&uuml;r das Jahr der Passausstellung
            <input type="hidden" name="Bezeichnung[]" value="Aufnahmegebühr - Ausweis einschließlich Sichtvermerk für das Jahr der Passausstellung" />
        </td>
        <td>
            <input id="menge_20"  style="text-align: center;" class="yellow menge" type="text" name="Menge[]" value="" />
            <input id="preis_20" type="hidden" value="' . $preis_20 . '" />
        </td>
        <td style="text-align: right;">
            ' . number_format( $preis_20, 2, ",", "." ) . ' &euro;
        </td>
        <td style="text-align: center;">
            <input class="gesamt" style="width:70%;text-align:right;" id="gesamt_20" type="text" name="Preis[]" value="" readonly="readonly" /> &euro;
        </td>
    </tr>
    <!-- ########################### Dritte Zeile ######################################################## -->
    <tr>
        <td><input style="text-align: center;" type="text" name="Pos[]" value="30" readonly="readonly" /></td>
        <td>
            <strong><ins>Pr&uuml;fungsgeb&uuml;hr:</ins></strong><br />
            Urkunde inkl. Pr&uuml;fungsmarke
            <input type="hidden" name="Bezeichnung[]" value="Prüfungsgebühr - Urkunde inkl. Prüfungsmarke" />
        </td>
        <td>
            <input id="menge_30"  style="text-align: center;" class="yellow menge" type="text" name="Menge[]" value="" />
            <input id="preis_30" type="hidden" value="' . $preis_30 . '" />
        </td>
        <td style="text-align: right;">
            ' . number_format( $preis_30, 2, ",", "." ) . ' &euro;
        </td>
        <td style="text-align: center;">
            <input class="gesamt" style="width:70%;text-align:right;" id="gesamt_30" type="text" name="Preis[]" value="" readonly="readonly" /> &euro;
        </td>
    </tr>
    <!-- ########################### Vierte Zeile ######################################################## -->
<!--
    <tr>
        <td><input style="text-align: center;" type="text" name="Pos[]" value="neu" readonly="readonly" /></td>
        <td>
            <strong><ins>NWTU Ansteckpin (10er Pack / Stck 0,80 &euro;)</ins></strong>
            
            <input type="hidden" name="Bezeichnung[]" value="NWTU Ansteckpin 10er Pack" />
        </td>
        <td>
            <input id="menge_neu" style="text-align: center;" class="yellow menge" type="text" name="Menge[]" value="" />
            <input id="preis_neu" type="hidden" value="' . $preis_neu . '" />
        </td>
        <td style="text-align: right;">
            ' . number_format( $preis_neu, 2, ",", "." ) . ' &euro;
        </td>
        <td style="text-align: center;">
            <input class="gesamt" style="width:70%;text-align:right;" id="gesamt_neu" type="text" name="Preis[]" value="" readonly="readonly" /> &euro;
        </td>
    </tr>
-->
    <tr>
        <td><input style="text-align: center;" type="text" name="Pos[]" value="70" readonly="readonly" /></td>
        <td>
            <strong><ins>Versandkostenpauschale</ins></strong>
            <input type="hidden" name="Bezeichnung[]" value="Versandkostenpauschale" />
        </td>
        <td>
            <input id="menge_70" type="hidden" name="Menge[]" value="1" />
            <input id="preis_70" type="hidden" value="' . $preis_70 . '" />
        </td>
        <td style="text-align: right;">

        </td>
        <td style="text-align: center;">
            <input class="gesamt" style="width:70%;text-align:right;" id="gesamt_70" type="text" name="Preis[]" value="' . number_format( $preis_70, 2 ) . '" readonly="readonly" /> &euro;
        </td>
    </tr>
    <tr>
        <td style="text-align: right;" colspan="4"><strong>Gesamt:</strong></td>
        <td style="text-align: center;"><input id="gesamtsumme" style="width:70%;text-align: right;" type="text" name="Gesamtsumme" value="" readonly="readonly" /> &euro;</td>
    </tr>
    <tr>
        <td colspan="5">
            <strong><ins>Lieferung nur gegen Vorauskasse</ins></strong>
            <p>Einzugserm&auml;chtigung (Nur f&uuml;r diesen Auftrag)<br />
            Bitte buchen Sie den Betrag von meinem/unserem Konto ab:</p>
        </td>
    </tr>
    <tr>
        <td colspan="2">IBAN: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="IBAN" value="" required=required />
            <input type="hidden" name="req[]" value="IBAN" />
        </td>
    </tr>
    <tr>
        <td colspan="2">Kontoinhaber: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="Kontoinhaber" value="" required=required />
            <input type="hidden" name="req[]" value="Kontoinhaber" />
        </td>
    </tr>
    <tr>
        <td colspan="2">Geldinstitut: *</td>
        <td colspan="3">
            <input class="req yellow" type="text" name="Geldinstitut" value="" required=required />
            <input type="hidden" name="req[]" value="Geldinstitut" />
        </td>
    </tr>
	<tr>
		<td colspan="5">
			<input type="checkbox" name="Datenschutzbestätigung" id="user_confirm_order" class="req yellow" />
			<input type="hidden" name="req[]" value="Datenschutzbestätigung" />
			<p><label for="user_confirm_order"><b>Ich stimme zu, dass meine Angaben aus dem Formular zur Beantwortung
meiner Anfrage erhoben und verarbeitet werden.</b>
<br>
 Die Daten werden nach
abgeschlossener Bearbeitung Ihrer Anfrage gelöscht. Hinweis: Sie können Ihre
Einwilligung jederzeit für die Zukunft per E-Mail an office@nwtu.de widerrufen.
Detaillierte Informationen zum Umgang mit Nutzerdaten finden Sie in unserer
Datenschutzerklärung</label></p>

		</td>
	</tr>
    <tr>
        <td colspan="5">
            <input type="hidden" name="action" value="send_bestellung" />
            <input id="submit_button" style="width:100%;padding: 5px;" type="button" value="Bestellung absenden" />
        </td>
    </tr>
    
</table>
</form>

';
            return $html;
        }
    }
