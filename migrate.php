<?php
/*
  Descrição do Desafio:
    Você precisa realizar uma migração dos dados fictícios que estão na pasta <dados_sistema_legado> para a base da clínica fictícia MedicalChallenge.
    Para isso, você precisa:
      1. Instalar o MariaDB na sua máquina. Dica: Você pode utilizar Docker para isso;
      2. Restaurar o banco da clínica fictícia Medical Challenge: arquivo <medical_challenge_schema>;
      3. Migrar os dados do sistema legado fictício que estão na pasta <dados_sistema_legado>:
        a) Dica: você pode criar uma função para importar os arquivos do formato CSV para uma tabela em um banco temporário no seu MariaDB.
      4. Gerar um dump dos dados já migrados para o banco da clínica fictícia Medical Challenge.
*/

// Importação de Bibliotecas:
include "./lib.php";

// Conexão com o banco da clínica fictícia:
$connMedical = mysqli_connect("localhost", "root", "d123", "MedicalChallenge")
  or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
$connTemp = mysqli_connect("localhost", "root", "d123", "0temp")
  or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

// Ignorar cabeçalho das tabelas do CSV
$start_row = 1;
$start_row2 = 1;
$start_row3 = 1;

if (($handle = fopen("./dados_sistema_legado/20210512_agendamentos.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
    if ($start_row == 1) {
      $start_row++;
      continue;
    } // Ignorar primeira linha

    $currentConvenio = $data[10];
    $currentProcedimento = $data[11];
    $currentProfissional = $data[8];

    $selectConvenio = "SELECT id FROM convenios WHERE nome = '$currentConvenio'";
    $checkConvenio = mysqli_query($connTemp, $selectConvenio);
    $insertConvenio = "INSERT INTO convenios (nome) VALUES ('$currentConvenio')";

    if ($checkConvenio->num_rows == 0) { // Verifica se o convênio já está cadastrado
      mysqli_query($connTemp, $insertConvenio);
    } else {
      echo "Convênio já cadastrado: $currentConvenio \n";
    }

    $selectProcedimento = "SELECT id FROM procedimentos WHERE nome = '$currentProcedimento'";
    $checkProcedimento = mysqli_query($connTemp, $selectProcedimento);
    $insertProcedimento = "INSERT INTO procedimentos (nome) VALUES ('$currentProcedimento')";

    if ($checkProcedimento->num_rows == 0) { // Verifica se o procedimento já está cadastrado
      mysqli_query($connTemp, $insertProcedimento);
    } else {
      echo "Procedimento já cadastrado: $currentProcedimento \n";
    }

    $selectProfissional = "SELECT id FROM profissionais WHERE nome = '$currentProfissional'";
    $checkProfissional = mysqli_query($connTemp, $selectProfissional);
    $insertProfissional = "INSERT INTO profissionais (nome) VALUES ('$currentProfissional')";

    if ($checkProfissional->num_rows == 0) { // Verifica se o profissional já está cadastrado
      mysqli_query($connTemp, $insertProfissional);
    } else {
      echo "Profissional já cadastrado: $currentProfissional \n";
    }

  }

  fclose($handle);
}


if (($handle = fopen("./dados_sistema_legado/20210512_pacientes.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
    if ($start_row2 == 1) {
      $start_row2++;
      continue;
    } // Ignorar primeira linha

    $nome = $data[1];
    $sexo = 1; // Previne campos nulos, padrão masculino
    $nascimento = $data[2];
    $cpf = $data[5];
    $rg = $data[6];
    $nomeConvenio = $data[9];
.
    // Formata o nascimento
    $nascimentoFormat = substr($nascimento, 6, 9) . "-" . substr($nascimento, 3, 2) . "-" . substr($nascimento, 0, 2);

    if ($data[7] === "M") {
      $sexo = 1;
    } else {
      $sexo = 2;
    }

    $selectConvenio = "SELECT id from convenios WHERE nome = '$nomeConvenio'";
    $checkConvenio = mysqli_query($connTemp, $selectConvenio);
    $idConvenio = mysqli_fetch_row($checkConvenio); // Pega o convênio do paciente

    $selectPaciente = "SELECT id FROM pacientes WHERE nome = '$nome'";
    $checkPaciente = mysqli_query($connTemp, $selectPaciente);

    $insertPaciente = "INSERT INTO pacientes (nome, sexo, nascimento, cpf, rg, id_convenio) VALUES ('$nome', '$sexo', '$nascimentoFormat', '$cpf', '$rg', '$idConvenio[0]')";

    if ($checkPaciente->num_rows == 0) { // Verifica se o paciente já está cadastrado
      mysqli_query($connTemp, $insertPaciente);
    } else {
      echo "Paciente já cadastrado: $nome \n";
    }

  }

  fclose($handle);
}

$counter = 1; // Variável para identificar a linha atual

if (($handle = fopen("./dados_sistema_legado/20210512_agendamentos.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
    if ($start_row3 == 1) {
      $start_row3++;
      continue;
    } // Ignorar primeira linha

    $counter++;

    $nomePaciente = $data[6];
    $nomeProfissional = $data[8];
    $dataAgend = $data[2];
    $horaInicio = $data[3];
    $horaFim = $data[4];
    $nomeProcedimento = $data[11];
    $observacoes = $data[1];

    // Formatar data
    $dataFormat = substr($dataAgend, 6, 9) . "-" . substr($dataAgend, 3, 2) . "-" . substr($dataAgend, 0, 2);
    $dhInicio = $dataFormat . " " . $horaInicio;
    $dhFim = $dataFormat . " " . $horaFim;

    $selectPaciente = "SELECT id, id_convenio from pacientes WHERE nome = '$nomePaciente'";
    $checkPaciente = mysqli_query($connTemp, $selectPaciente);
    $dadosPaciente = mysqli_fetch_row($checkPaciente); // Pega o id e id_convenio do paciente

    $selectProfissional = "SELECT id from profissionais WHERE nome = '$nomeProfissional'";
    $checkProfissional = mysqli_query($connTemp, $selectProfissional);
    $dadosProfissional = mysqli_fetch_row($checkProfissional); // Pega o id do profissional

    $selectProcedimento = "SELECT id from procedimentos WHERE nome = '$nomeProcedimento'";
    $checkProcedimento = mysqli_query($connTemp, $selectProcedimento);
    $dadosProcedimento = mysqli_fetch_row($checkProcedimento); // Pega o id do procedimento

    $insertAgendamento = "INSERT INTO agendamentos (id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes) VALUES ('$dadosPaciente[0]', '$dadosProfissional[0]', '$dhInicio', '$dhFim', '$dadosPaciente[1]', '$dadosProcedimento[0]', '$observacoes')";

    // Verifica se não existem campos nulos
    if ($checkPaciente->num_rows > 0 && $checkProfissional->num_rows > 0 && $checkProcedimento->num_rows > 0) {
      mysqli_query($connTemp, $insertAgendamento);
    } else {
      echo "\n ERRO NA LINHA $counter DO CSV\n";
    }

  }

  fclose($handle);
}


// Parte de dump do banco temporário para o banco medicalchallenge

$selectConvenios = "SELECT nome, descricao from convenios";
$selectProcedimentos = "SELECT nome, descricao from procedimentos";
$selectProfissionais = "SELECT nome, crm from profissionais";
$selectPacientes = "SELECT pacientes.nome, pacientes.sexo, pacientes.nascimento, pacientes.cpf, pacientes.rg, convenios.nome as 'nome_convenio', pacientes.cod_referencia from pacientes INNER JOIN convenios ON pacientes.id_convenio = convenios.id";
$selectAgendamentos = "SELECT pacientes.nome as 'nome_paciente', profissionais.nome as 'nome_profissional', dh_inicio, dh_fim, convenios.nome as 'nome_convenio', procedimentos.nome as 'nome_procedimento', observacoes from agendamentos 
INNER JOIN pacientes ON agendamentos.id_paciente = pacientes.id
inner join profissionais on agendamentos.id_profissional = profissionais.id
inner join convenios on agendamentos.id_convenio = convenios.id
inner join procedimentos on agendamentos.id_procedimento = procedimentos.id";

$checkConvenios = mysqli_query($connTemp, $selectConvenios);
$checkProcedimentos = mysqli_query($connTemp, $selectProcedimentos);
$checkProfissionais = mysqli_query($connTemp, $selectProfissionais);
$checkPacientes = mysqli_query($connTemp, $selectPacientes);
$checkAgendamentos = mysqli_query($connTemp, $selectAgendamentos);

while ($row = $checkConvenios->fetch_assoc()) {
  $convenios[] = $row;
}

while ($row = $checkProcedimentos->fetch_assoc()) {
  $procedimentos[] = $row;
}

while ($row = $checkProfissionais->fetch_assoc()) {
  $profissionais[] = $row;
}

while ($row = $checkPacientes->fetch_assoc()) {
  $pacientes[] = $row;
}

while ($row = $checkAgendamentos->fetch_assoc()) {
  $agendamentos[] = $row;
}

for ($i = 0; $i < count($convenios); $i++) {
  $nome = $convenios[$i]['nome'];
  $descricao = $convenios[$i]['descricao'];

  $insertConvenios = "INSERT INTO convenios (nome, descricao) VALUES ('$nome', '$descricao')";

  mysqli_query($connMedical, $insertConvenios);

}

for ($i = 0; $i < count($procedimentos); $i++) {
  $nome = $procedimentos[$i]['nome'];
  $descricao = $procedimentos[$i]['descricao'];

  $insertProcedimentos = "INSERT INTO procedimentos (nome, descricao) VALUES ('$nome', '$descricao')";

  mysqli_query($connMedical, $insertProcedimentos);

}

for ($i = 0; $i < count($profissionais); $i++) {
  $nome = $profissionais[$i]['nome'];
  $crm = $profissionais[$i]['crm'];

  $insertProfissionais = "INSERT INTO profissionais (nome, crm) VALUES ('$nome', '$crm')";

  mysqli_query($connMedical, $insertProfissionais);

}


// NÃO CONSEGUI:

/*
for ($i = 0; $i < count($pacientes); $i++) {
  $nome = $pacientes[$i]['nome'];
  $sexo = $pacientes[$i]['sexo'];
  $nascimento = $pacientes[$i]['nascimento'];
  $cpf = $pacientes[$i]['cpf'];
  $rg = $pacientes[$i]['rg'];
  $nomeConvenio = $pacientes[$i]['nome_convenio'];
  $codReferencia = $pacientes[$i]['cod_referencia'];

  $insertPacientes = "INSERT INTO pacientes (nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) 
  VALUES ('$nome', '$sexo', '$nascimento', '$cpf', '$rg', convenios.id) INNER JOIN convenios ON pacientes.id_convenio = convenios.id";

  mysqli_query($connMedical, $insertProfissionais);

}
*/


// Encerrando as conexões:
$connMedical->close();
$connTemp->close();

// Informações de Fim da Migração:
echo "\nFim da Migração: " . dateNow() . ".\n";