<?php
    header('Content-Type: application/json');
    function obterInformacoesCEP($cep){
        $url = "https://viacep.com.br/ws/$cep/json/";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);        
        curl_close($ch);

        return $response;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST'){
        $cnpj = isset($_GET['cnpj']) ? $_GET['cnpj'] : null;
        $cep = isset($_GET['cep']) ? $_GET['cep'] : null;

        if (strlen($cnpj ) != 14 ) {

            http_response_code(400);
            echo json_encode(['error' => 'CNPJ invalido.']);

        }if(strlen($cep) != 8) {

            http_response_code(400);
            echo json_encode(['error' => 'CEP invalido.']);

        }
        else{
            if($cnpj && $cep) {
                $infoCep = obterInformacoesCEP($cep);
                $infoCepArray = json_decode($infoCep, true);
            
                if($infoCepArray === null){
                    http_response_code(400);
                    echo json_encode(['error' => 'Cep nao localizado.']);
                }else{
                    $dadosJson =  json_encode([
                        'cnpj' => $cnpj,
                        'cep' => $cep,
                        'logradouro' => isset($infoCepArray['logradouro']) ? removerCaracteresEspeciais($infoCepArray['logradouro'] ): null,
                        'bairro' => isset($infoCepArray['bairro']) ? removerCaracteresEspeciais($infoCepArray['bairro']) : null,
                        'cidade' => isset($infoCepArray['localidade']) ? removerCaracteresEspeciais($infoCepArray['localidade'] ): null,
                        'estado' => isset($infoCepArray['uf']) ? $infoCepArray['uf'] : null
                    ]);
        
                    processarDadosJson($dadosJson);
                }
            

            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Parametros cnpj e cep sao obrigatorios.']);
            }
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo nao permitido. Utilize GET ou POST.']);
    }

    
    
    function processarDadosJson($json){    
        $dados = json_decode($json, true);
        
        if ($dados === null) {
            echo 'Erro ao decodificar o JSON.';
        }

        $cnpj = $dados['cnpj'];
        $cep = $dados['cep'];
        $logradouro = isset($dados['logradouro']) ? $dados['logradouro'] : null;
        $bairro = isset($dados['bairro']) ? $dados['bairro'] : null;
        $cidade = isset($dados['cidade']) ? $dados['cidade'] : null;
        $estado = isset($dados['estado']) ? $dados['estado'] : null;

        $update = AtualizarBancoDeDados($cnpj, $cep, $logradouro, $bairro, $cidade, $estado);
       
        if ($update === true) {
            echo $json;
            echo "\n\n";
            echo  'Informações atualizadas com sucesso.';   

        } else {
            echo 'Erro ao atualizar as informações';
        }
    }

    // Função para atualizar os campos no banco de dados
    function atualizarBancoDeDados($cnpj, $cep, $logradouro, $bairro, $cidade, $estado) {
        // Consulta a empresa pelo CNPJ
        $empresa = consultarEmpresaPorCNPJ($cnpj);
        
        if ($empresa === true) {
            // Atualiza os campos da empresa

            // $empresaId = $empresa['id'];  // Suponha que 'id' seja o campo que identifica exclusivamente a empresa           
            // $sql = "UPDATE empresas SET cep='$cep', logradouro='$logradouro', bairro='$bairro', cidade='$cidade', estado='$estado' WHERE id=$empresaId";

            return true;
        } else {
            return false;
        }
    }

     // Função para consultar a empresa pelo CNPJ
     // Só será atualizada os dados das empresas cadastradas na base

     function consultarEmpresaPorCNPJ($cnpj) {

        // $servername = "localhost:4000"; 
        // $username = "root"; 
        // $password = ""; 
        // $dbname = "testeapi";

        // $conn = new mysqli($servername, $username, $password, $dbname);
    
        // if ($conn->connect_error) {
        //     die("Conexão falhou: " . $conn->connect_error);
        // }
        // $sql = "SELECT * FROM empresas WHERE cnpj = '$cnpj'";
        // $result = $conn->query($sql);

        // if ($result->num_rows > 0) {
        //     return true;
        // } else {
        //     return false;
        // }

        return true;
    }

    //Função para remover caracteres especiais
    function removerCaracteresEspeciais($valor){    
        $str = preg_replace('/[^a-zA-Z0-9\s]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $valor));

        return $str;
    }


?>