<?php
if(!defined('OSTSCPINC') || !is_object($ticket) || !is_object($thisuser) || !$thisuser->isStaff()) die('Acesso Negado');

if(!($thisuser->canEditTickets() || ($thisuser->isManager() && $ticket->getDeptId()==$thisuser->getDeptId()))) die('Acesso negado. Erro permanente.');

if($_POST && $errors){
    $info=Format::input($_POST);
}else{
    $info=array('email'=>$ticket->getEmail(),
                'name' =>$ticket->getName(),
                'phone'=>$ticket->getPhone(),
                'phone_ext'=>$ticket->getPhoneExt(),
                'pri'=>$ticket->getPriorityId(),
                'topicId'=>$ticket->getTopicId(),
                'topic'=>$ticket->getHelpTopic(),
                'subject' =>$ticket->getSubject(),
                'duedate' =>$ticket->getDueDate()?(Format::userdate('m/d/Y',Misc::db2gmtime($ticket->getDueDate()))):'',
                'time'=>$ticket->getDueDate()?(Format::userdate('G:i',Misc::db2gmtime($ticket->getDueDate()))):'',
                );
    /*Note: Please don't make me explain how dates work - it is torture. Trust me! */
}

?>
<div width="100%">
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" class="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p class="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<table width="100%" border="0" cellspacing=1 cellpadding=2>
  <form action="tickets.php?id=<?=$ticket->getId()?>" method="post">
    <input type='hidden' name='id' value='<?=$ticket->getId()?>'>
    <input type='hidden' name='a' value='update'>
    <tr><td align="left" colspan=2 class="msg">
        Atualização de Ticket #<?=$ticket->getExtId()?>&nbsp;&nbsp;(<a href="tickets.php?id=<?=$ticket->getId()?>" style="color:black;">View Ticket</a>)<br></td></tr>
    <tr>
        <td align="left" nowrap width="120"><b>Endereço de e-mail:</b></td>
        <td>
            <input type="text" id="email" name="email" size="25" value="<?=$info['email']?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['email']?></font>
        </td>
    </tr>
    <tr>
        <td align="left" ><b>Nome completo:</b></td>
        <td>
            <input type="text" id="name" name="name" size="25" value="<?=$info['name']?>">
            &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['name']?></font>
        </td>
    </tr>
    <tr>
        <td align="left"><b>Assunto:</b></td>
        <td>
            <input type="text" name="subject" size="35" value="<?=$info['subject']?>">
            &nbsp;<font class="error">*&nbsp;<?=$errors['subject']?></font>
        </td>
    </tr>
    <tr>
        <td align="left">Telefone:</td>
        <td><input type="text" name="phone" size="25" value="<?=$info['phone']?>">
             &nbsp;Ext&nbsp;<input type="text" name="phone_ext" size="6" value="<?=$info['phone_ext']?>">
            &nbsp;<font class="error">&nbsp;<?=$errors['phone']?></font></td>
    </tr>
    <tr height=1px><td align="left" colspan=2 >&nbsp;</td></tr>
    <tr>
        <td align="left" valign="top">Data de Vencimento:</td>
        <td>
            <i>O horário é baseado na sua localização (GM <?=$thisuser->getTZoffset()?>)</i>&nbsp;<font class="error">&nbsp;<?=$errors['time']?></font><br>
            <input id="duedate" name="duedate" value="<?=Format::htmlchars($info['duedate'])?>"
                onclick="event.cancelBubble=true;calendar(this);" autocomplete=OFF>
            <a href="#" onclick="event.cancelBubble=true;calendar(getObj('duedate')); return false;"><img src='images/cal.png'border=0 alt=""></a>
            &nbsp;&nbsp;
            <?php
             $min=$hr=null;
             if($info['time'])
                list($hr,$min)=explode(':',$info['time']);
                echo Misc::timeDropdown($hr,$min,'time');
            ?>
            &nbsp;<font class="error">&nbsp;<?=$errors['duedate']?></font>
        </td>
    </tr>
    <?
      $sql='SELECT priority_id,priority_desc FROM '.TICKET_PRIORITY_TABLE.' ORDER BY priority_urgency DESC';
      if(($priorities=db_query($sql)) && db_num_rows($priorities)){ ?>
      <tr>
        <td align="left">Prioridade:</td>
        <td>
            <select name="pri">
              <?
                while($row=db_fetch_array($priorities)){ ?>
                    <option value="<?=$row['priority_id']?>" <?=$info['pri']==$row['priority_id']?'selected':''?> ><?=$row['priority_desc']?></option>
              <?}?>
            </select>
        </td>
       </tr>
    <? }?>

    <?php
    $services= db_query('SELECT topic_id,topic,isactive FROM '.TOPIC_TABLE.' ORDER BY topic');
    if($services && db_num_rows($services)){ ?>
    <tr>
        <td align="left" valign="top">Tópicos de ajuda:</td>
        <td>
            <select name="topicId">    
                <option value="0" selected >Nenhum</option>
                <?if(!$info['topicId'] && $info['topic']){ //old helptopic?>
                <option value="0" selected ><?=$info['topic']?> (eliminado)</option>
                <?
                }
                 while (list($topicId,$topic,$active) = db_fetch_row($services)){
                    $selected = ($info['topicId']==$topicId)?'selected':'';
                    $status=$active?'Ativo':'Inativo';
                    ?>
                    <option value="<?=$topicId?>"<?=$selected?>><?=$topic?>&nbsp;&nbsp;&nbsp;(<?=$status?>)</option>
                <?
                 }?>
            </select>
            &nbsp;(optional)<font class="error">&nbsp;<?=$errors['topicId']?></font>
        </td>
    </tr>
    <?
    }?>
    <tr>
        <td align="left" valign="top"><b>Nota interna:</b></td>
        <td>
            <i>Razões para a edição.</i><font class="error"><b>*&nbsp;<?=$errors['note']?></b></font><br/>
            <textarea name="note" cols="45" rows="5" wrap="soft"><?=$info['note']?></textarea></td>
    </tr>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td></tr>
    <tr>
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Carregar Ticket">
            <input class="button" type="reset" value="Redefinir">
            <input class="button" type="button" name="cancel" value="Cancelar" onClick='window.location.href="tickets.php?id=<?=$ticket->getId()?>"'>    
        </td>
    </tr>
  </form>
</table>
