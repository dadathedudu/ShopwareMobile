{if $sBasketInfo}
{['success'=>false, 'msg'=> 'Ihre Bestellung konnte nicht abgesendet werden. Bitte probieren Sie es zu einen sp�teren Zeitpunkt erneut.'|utf8_encode]|json_encode}
{else}
{['success'=>true, 'msg'=> 'In K�rze erhalten Sie eine Bestellbest�tigungsmail.'|utf8_encode, 'basket'=>$sBasket, 'userdata'=>$sUserData]|json_encode}
{/if}