<?php
if(!defined('OSTSCPINC') or !is_object($thisuser) or !$thisuser->canManageKb()) die('Acesso Negado');

//List premade answers.
$select='SELECT premade.*,dept_name ';
$from='FROM '.KB_PREMADE_TABLE.' premade LEFT JOIN '.DEPT_TABLE.' USING(dept_id) ';

//make sure the search query is 3 chars min...defaults to no query with warning message
if($_REQUEST['a']=='search') {
    if(!$_REQUEST['query'] || strlen($_REQUEST['query'])<3) {
        $errors['err']='O termo a pesquisar deve ter mais de 3 caracteres';
    }else{
        //fulltext search.
        $search=true;
        $qstr.='&a='.urlencode($_REQUEST['a']);
        $qstr.='&query='.urlencode($_REQUEST['query']);
        $where=' WHERE MATCH(title,answer) AGAINST ('.db_input($_REQUEST['query']).')';
        if($_REQUEST['dept'])
            $where.=' AND dept_id='.db_input($_REQUEST['dept']);
    }
}

//I admit this crap sucks...but who cares??
$sortOptions=array('createdate'=>'premade.created','updatedate'=>'premade.updated','title'=>'premade.title');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
//Sorting options...
if($_REQUEST['sort']) {
    $order_column =$sortOptions[$_REQUEST['sort']];
}

if($_REQUEST['order']) {
    $order=$orderWays[$_REQUEST['order']];
}


$order_column=$order_column?$order_column:'premade.title';
$order=$order?$order:'DESC';

$order_by=$search?'':" ORDER BY $order_column $order ";


$total=db_count('SELECT count(*) '.$from.' '.$where);
$pagelimit=$thisuser->getPageLimit();
$pagelimit=$pagelimit?$pagelimit:PAGE_LIMIT; //true default...if all fails.
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,$pagelimit);
$pageNav->setURL('kb.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$query="$select $from $where $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$replies = db_query($query);
$showing=db_num_rows($replies)?$pageNav->showing():'';
$results_type=($search)?'Resultados da Pesquisa':'Premade/Canned Replies';
$negorder=$order=='DESC'?'ASC':'DESC'; //Negate the sorting..
?>
<div>
    <?if($errors['err']) {?>
        <p align="center" id="errormessage"><?=$errors['err']?></p>
    <?}elseif($msg) {?>
        <p align="center" id="infomessage"><?=$msg?></p>
    <?}elseif($warn) {?>
        <p id="warnmessage"><?=$warn?></p>
    <?}?>
</div>
<div align="left">
    <form action="kb.php" method="GET" >
    <input type='hidden' name='a' value='pesquisar'>
    Procurar por:&nbsp;<input type="text" name="query" value="<?=Format::htmlchars($_REQUEST['query'])?>">
    categoria
    <select name="dept">
            <option value=0>Todos Departamentos</option>
            <?
            $depts= db_query('SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE dept_id!='.db_input($ticket['dept_id']));
            while (list($deptId,$deptName) = db_fetch_row($depts)){
                $selected = ($_GET['dept']==$deptId)?'selected':''; ?>
                <option value="<?=$deptId?>"<?=$selected?>>&nbsp;&nbsp;<?=$deptName?></option>
           <?}?>
    </select>
    &nbsp;
    <input type="submit" name="search" class="button" value="Avançar">
    </form>
</div>
<div class="msg"><?=$result_type?>&nbsp;<?=$showing?></div>
<table width="100%" border="0" cellspacing=1 cellpadding=2>
   <form action="kb.php" method="POST" name="premade" onSubmit="return checkbox_checker(document.forms['premade'],1,0);">
   <input type=hidden name='a' value='process'>
   <tr><td>
     <table border="0" cellspacing=0 cellpadding=2 class="dtable" align="center" width="100%">
        <tr>
	        <th width="7px">&nbsp;</th>
	        <th>
                <a href="kb.php?sort=title&order=<?=$negorder?><?=$qstr?>" title="Ordenar Por Título <?=$negorder?>">Título da Resposta</a></th>
            <th width=50>Estado</th>
	        <th width=200>Categoria/Departamento</th> 
	        <th width=150 nowrap>
                <a href="kb.php?sort=updatedate&order=<?=$negorder?><?=$qstr?>" title="Ordenar Por Data de Atualização <?=$negorder?>">Última Atualização</a></th>
        </tr>
        <?
        $class = 'row1';
        $total=0;
        $grps=($errors && is_array($_POST['grps']))?$_POST['grps']:null;
        if($replies && db_num_rows($replies)):
            while ($row = db_fetch_array($replies)) {
                $sel=false;
                if($canned && in_array($row['premade_id'],$canned)){
                    $class="$class highlight";
                    $sel=true;
                }elseif($replyID && $replyID==$row['premade_id']) {
                    $class="$class highlight";
                }
                ?>
            <tr class="<?=$class?>" id="<?=$row['premade_id']?>">
                <td width=7px>
                  <input type="checkbox" name="canned[]" value="<?=$row['premade_id']?>" <?=$sel?'checked':''?> 
                        onClick="highLight(this.value,this.checked);">
                <td><a href="kb.php?id=<?=$row['premade_id']?>"><?=Format::htmlchars(Format::truncate($row['title'],60))?></a></td>
                <td><b><?=$row['isenabled']?'Ativado':'Desativado'?></b></td>
                <td><?=$row['dept_name']?Format::htmlchars($row['dept_name']):'Todos os Departamentos'?></td>
                <td><?=Format::db_datetime($row['updated'])?></td>
            </tr>
            <?
            $class = ($class =='row2') ?'row1':'row2';
            } //end of while.
        else: //nothin' found!! ?> 
            <tr class="<?=$class?>"><td colspan=6><b>Consulta retornou 0 resultados</b></td></tr>
        <?
        endif; ?>
    </table>
   </td></tr>
   <?
   if(db_num_rows($replies)>0): //Show options..
    ?>
   <tr><td style="padding-left:20px">
        Selecione:&nbsp;
        <a href="#" onclick="return select_all(document.forms['premade'],true)">Todos</a>&nbsp;
        <a href="#" onclick="return toogle_all(document.forms['premade'],true)">Aleatório</a>&nbsp;
        <a href="#" onclick="return reset_all(document.forms['premade'])">Nenhum</a>&nbsp;
        &nbsp;página:<?=$pageNav->getPageLinks()?>&nbsp;
    </td></td>
    <tr><td align="center">
            <input class="button" type="submit" name="enable" value="Ativar" 
                onClick='return confirm("Tem certeza de que deseja ATIVAR entradas selecionadas?");'>
            <input class="button" type="submit" name="disable" value="Desativar" 
                onClick='return confirm("Tem certeza de que deseja DESATIVAR entradas selecionadas?");'>
            <input class="button" type="submit" name="delete" value="Apagar" 
                onClick='return confirm("Tem certeza de que quer APAGAR entradas selecionadas?");'>
    </td></td>
    <?
    endif;
    ?>
   </form>
 </table>
