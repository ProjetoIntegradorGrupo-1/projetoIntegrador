<?php
// processar_recuperacao.php
// O sistema não envia e-mails. A senha é redefinida pelo administrador (ou pelo supervisor da pessoa)
// na tela de edição de usuário. A resposta é sempre a mesma para não revelar quais e-mails existem.
header("Location: esqueciSenha.html?enviado=1");
exit;
