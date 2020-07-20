<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new \Slim\App;

require __DIR__ . '/../class/auth.php';
require __DIR__ . '/../class/estadisticas.php';
require __DIR__ . '/../class/classRegistros.php';
require __DIR__ . '/../class/crearUsuario.php';
require __DIR__ . '/../config/db.php';

use \Firebase\JWT\JWT;



//creacion de usuario, solo con filtro de email y nick
$app->post('/api/creacion/usuarios', function (Request $request, Response $response) { 
   $body = json_decode($request->getBody());
    $nick = $body->{'nick'};
    $email = $body->{'email'};
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $pass = $body->{'pass'};
    $rol = $body->{'id_rol'};           
    $hidrologica = $body->{'id_hidrologica'};           
    
    $check = array($nick , $pass ,$email , $rol , $hidrologica );
    $contador = 0;

    for ($i=0; $i < count($check) ; $i++) { 
        if (!isset($check[$i])) {
                $contador++;
            }
        }
           
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return "El email no es valido";
            }else  if ($contador === 0){
                $usuarios = new Usuarios($nick , $pass);
                return $usuarios->creacion($body , $email , $rol , $hidrologica);
            }else{
                return 'Hay variables que no estan definida';
            }
           
            
     });

     $app->post('/api/info/user', function (Request $request, Response $response) { 
      //  $body = json_decode($request->getBody());
        $nick = "Alex";//json_decode($body->body);
       
        
            

            try {
                $sql = "SELECT usuarios.id_hidrologica FROM usuarios WHERE usuarios.nick = ?";
                $db = new DB();
                $db=$db->connection('usuarios_m_soluciones');
                $stmt = $db->prepare($sql); 
                $stmt->bind_param("s", $nick);
                $stmt->execute();
                $resultado = $stmt->get_result();
                $resultado = $resultado->fetch_object();
                $id_hidrologica = $resultado->id_hidrologica;                              
                
                if ($stmt) {
                    
                    $sql = "SELECT hidrologicas.* FROM hidrologicas WHERE hidrologicas.id_hidrologica = ?";
                    $db = new DB();
                    $db=$db->connection('mapa_soluciones');
                    $stmt = $db->prepare($sql); 
                    $stmt->bind_param("i", $id_hidrologica );
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $resultado = $resultado->fetch_object();
                    $estado1 = $resultado->id_estado;  
                    $estado2 = $resultado->id_estado2;
                    $estado3 = $resultado->id_estado3;
                    $hidrologica = $resultado;
                    

                    if ($stmt) {
                        $sql = "SELECT estados.id_estado, estados.estado
                                FROM estados
                                WHERE estados.id_estado 
                                IN (? , ? , ?)";
                        $db = new DB();
                        $db=$db->connection('mapa_soluciones');
                        $stmt = $db->prepare($sql); 
                        $stmt->bind_param("iii", $estado1 , $estado2 , $estado3 );
                        $stmt->execute();
                        $resultado = $stmt->get_result();
                        $resultado = $resultado->fetch_all(MYSQLI_ASSOC);

                        $array = [
                            "hidrologica" => $hidrologica,
                            "estados" => $resultado
                        ];
                          return $response->withJson($array);

                    }

"SELECT datos.id_tipo_solucion, soluciones.solucion ,COUNT(proyectos.id_proyecto) as cantidad 
                FROM proyectos 
                LEFT JOIN datos ON proyectos.id_datos = datos.id_datos
                LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion 
                WHERE datos.id_tipo_solucion IN (1, 2, 3, 4)
                GROUP BY datos.id_tipo_solucion";
                   
                }
                   
                
                        
                
             } 
            catch (MySQLDuplicateKeyException $e) {
                $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
             });
    
  






     
     
     




$app->post('/authenticate', function (Request $request, Response $response) {
    $body = json_decode($request->getBody());

    $sql = "SELECT `usuarios`.*
            FROM `usuarios`";
    $db = new DB();
    $resultado = $db->consultaSinParametros('usuarios_m_soluciones', $sql);
    
    
    $body=json_decode($body->body);
    
    
    foreach ($resultado[0] as $key => $user) {
    if ($user['nick'] == $body->user && $user['pass'] == $body->pass) {
        $current_user = $user;
    }}

    if (!isset($current_user)) {
        echo json_encode("No user found");
    } else{

        $sql = "SELECT * FROM tokens
             WHERE id_usuario_token  = ?";

        try {
            $db = new DB();
            $db = $db->connection('usuarios_m_soluciones');
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $current_user['id_usuario']);
            $stmt->execute();
            $stmt = $stmt->get_result();
             
            $token_from_db = $stmt->fetch_object();
            
            $db = null;
            if ($token_from_db) {
                return $response->withJson([
                "Token" => $token_from_db->token,
                "User_render" =>$current_user['id_rol'], 
               // "Hidrologica" =>$current_user
                ]);
            }    
            }catch (Exception $e) {
            $e->getMessage();
            }

        if (count($current_user) != 0 && !$token_from_db) {


             $data = [
                "user_login" => $current_user['nick'],
                "user_id"    => $current_user['id_usuario'],
                "user_rol"    => $current_user['id_rol']
            ];

             try {
                $token=Auth::SignIn($data);
             } catch (Exception $e) {
                 echo json_encode($e);
             }

              $sql = "INSERT INTO tokens (id_usuario_token, token)
                  VALUES (?, ?)";
              try {
                    $hoy = (date('Y-m-d', time()));
                    $db = new DB();
                    $db = $db->connection('usuarios_m_soluciones');
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param('is', $current_user['id_usuario'], $token);
                    $stmt->execute();
                    $db = null;
                    return $response->withJson([
                    "Token" => $token,
                    "User_render" =>$current_user['id_rol']
                    ]);
 
              } catch (PDOException $e) {
                  echo '{"error":{"text":' . $e->getMessage() . '}}';
              }
         }
    }

});

$app->get('/api/informacion/usuario/{ID_Usuario}', function (Request $request, Response $response) {
    $id = $request->getAttribute('ID_Usuario');

    $sql = "SELECT `usuarios`.`nick`  , `rol`.`rol` 
    FROM `usuarios`
        LEFT JOIN `rol` ON `usuarios`.`id_rol` = `rol`.`id_rol`
        WHERE `id_usuario` = ? ";
    
    
         
         try {
            $db = new DB();
            $db=$db->connection('usuarios_m_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
            return json_encode($resultado);

            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
     });



$app->get('/api/informacion/poblacion/beneficiada', function (Request $request, Response $response) { /* grafico arriba derecha, para ostrar la polacion y los litros por segundos del estado global*/
    

    $sql = "SELECT datos.id_tipo_solucion, soluciones.solucion, soluciones.solucion, SUM(poblacion.poblacion_final) AS poblacionFinal, SUM(lps.lps_final) AS lpsFinal 
            FROM proyectos LEFT JOIN datos ON proyectos.id_datos = datos.id_datos 
            LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion 
            LEFT JOIN poblacion ON proyectos.id_poblacion = poblacion.id_problacion 
            LEFT JOIN lps ON proyectos.id_lps = lps.id_lps 
            WHERE datos.id_tipo_solucion IN (1, 2, 3, 4) 
            GROUP BY datos.id_tipo_solucion";
    
    
    $proyecto = new proyecto();
         
         try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
            
             return $response->withJson($resultado);
               
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
     });

     /*SELECT `situaciones de servicio`.`situacion_de_servicio` , COUNT(proyectos.id_estado_proyecto) as cantidad FROM `situaciones de servicio` LEFT JOIN `proyectos` ON `proyectos`.`id_estado_proyecto` = `situaciones de servicio`.`id_situacion_de_servicio` WHERE `situaciones de servicio`.`id_situacion_de_servicio` IN (1, 2, 3) GROUP BY `situaciones de servicio`.`situacion_de_servicio` */


$app->get('/api/estadistica/situacion/servicio', function (Request $request, Response $response) { /*grafico abajo, centro, donde se muestra dos valores del estado de servicio global*/ 
    
        $sql  ="SELECT `situaciones de servicio`.`situacion_de_servicio` , COUNT(proyectos.id_estado_proyecto) as cantidad FROM `situaciones de servicio` LEFT JOIN `proyectos` ON `proyectos`.`id_estado_proyecto` = `situaciones de servicio`.`id_situacion_de_servicio` WHERE `situaciones de servicio`.`id_situacion_de_servicio` IN (1, 2, 3) GROUP BY `situaciones de servicio`.`situacion_de_servicio`";

        try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
            $db = null;
    
    
               
             return $response->withJson($resultado);
                    
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
         });


$app->get('/api/estadistica/proyecto', function (Request $request, Response $response) { /* para grafico de proyectos finalizados, en ejecuion y por iniciar*/
    
    $sql ="SELECT estatus.estatus ,COUNT(proyectos.id_estatus) as cantidad
        FROM proyectos 
        LEFT JOIN estatus ON proyectos.id_estatus = estatus.id_estatus 
        WHERE proyectos.id_estatus IN (0, 1, 2) 
        GROUP BY proyectos.id_estatus";

    try {
        $db = new DB();
        $db=$db->connection('mapa_soluciones');
        $stmt = $db->prepare($sql); 
        $stmt->execute();
        $stmt = $stmt->get_result();
        $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
        $db = null;
        
        if (count($resultado) === 0) {
                 
            $array = [
                
                [ 
                    "estatus" => "Por iniciar",
                    "cantidad" => 0
                ],
                    [ 
                    "estatus" => "En ejecucion",
                    "cantidad" => 0
                ],
                [ 
                    "estatus" => "Finalizado",
                    "cantidad" => 0
                ],  
                [ 
                "nombre"=> "Proyectos",
                "cantidad"=> 0,
                "porcentaje1" => 0,
                "porcentaje2" => 0,
                "porcentaje3" => 0
                ]
            ];
            return $array;
        }else{
            $suma = $resultado[0]['cantidad'] + $resultado[1]['cantidad'] + $resultado[2]['cantidad'];
            $porcentaje1 = ($resultado[0]['cantidad'] * 100) / $suma;
            $porcentaje2 = ($resultado[1]['cantidad'] * 100) / $suma;
            $porcentaje3 = ($resultado[2]['cantidad'] * 100) / $suma;
            $array = [  
                "nombre"=> "Proyectos",
                "cantidad"=> $suma,
                "porcentaje1" => round($porcentaje1),
                "porcentaje2" => round($porcentaje2),
                "porcentaje3" => round($porcentaje3)
            ];
            array_push($resultado , $array);
            return $response->withJson($resultado);
         }
        
                
        
     } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }
     });



$app->get('/api/estadistica/tipos/soluciones', function (Request $request, Response $response) { /*grafico arriba centro, para mostrar el porcentaje de proyectos por cada solucion */
    
        $sql  ="SELECT datos.id_tipo_solucion, soluciones.solucion ,COUNT(proyectos.id_proyecto) as cantidad 
                FROM proyectos 
                LEFT JOIN datos ON proyectos.id_datos = datos.id_datos
                LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion 
                WHERE datos.id_tipo_solucion IN (1, 2, 3, 4)
                GROUP BY datos.id_tipo_solucion";

        try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);

            if (count($resultado) === 0) {
                $array= [                     
                    [ 
                        "id_tipo_solucion" => 1,
                        "solucion" => "Local o Comunitaria",
                        "cantidad" => 0
                    ],
                    [ 
                        "id_tipo_solucion" => 2,
                        "solucion" => "Convencional ",
                        "cantidad" => 0
                    ],
                    [
                        
                        "id_tipo_solucion" => 3,
                        "solucion" => "Estructurante",
                        "cantidad" => 0
                    ]
                    ,
                    [ 
                        "id_tipo_solucion" => 4,
                        "solucion" => "En fuentes",
                        "cantidad" => 0
                    ]
                ];
                return $response->withJson($array);
            }else{
                return $response->withJson($resultado);
            }

                    
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
         });



$app->get('/api/estadistica/tipos/unidades', function (Request $request, Response $response) { 
    
        $sql  ="SELECT unidades.unidad, COUNT(acciones_especificas.id_unidades) as cantidad 
                        FROM unidades 
                LEFT JOIN acciones_especificas ON acciones_especificas.id_unidades = unidades.id_unidades 
                GROUP BY unidades.unidad";

        try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);

            return $response->withJson($resultado);
                    
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
         });



$app->get('/api/informacion/general/proyectos', function (Request $request, Response $response) { /* Mostrar proyetos con informacion minima y una vista previa*/
    

    $sql = "SELECT datos.accion_general, proyectos.id_proyecto, datos.nombre, estatus.estatus
            FROM proyectos 
            LEFT JOIN datos ON proyectos.id_datos = datos.id_datos 
            LEFT JOIN estatus ON proyectos.id_estatus = estatus.id_estatus";
         
         try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
            


           return $response->withJson($resultado);                  
               
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
     });




$app->get('/api/informacion/proyectos/hidrologicas', function (Request $request, Response $response) { /* grafica arriba izquierda mostrando la cantidad de proyectos*/
    

    $sql = "SELECT hidrologicas.hidrologica, COUNT(proyectos.id_proyecto) AS cantidad 
            FROM hidrologicas 
            LEFT JOIN proyectos on proyectos.id_hidrologica = hidrologicas.id_hidrologica 
            GROUP BY hidrologicas.id_hidrologica";
         
         try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->execute();
            $stmt = $stmt->get_result();
            $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
            $db = null;

            try{
                $sql2 = "SELECT hidrologicas.hidrologica, COUNT(proyectos.id_proyecto) AS finalizados 
                        FROM hidrologicas 
                        LEFT JOIN proyectos on proyectos.id_hidrologica = hidrologicas.id_hidrologica 
                        WHERE proyectos.id_estatus = 2 
                        GROUP BY hidrologicas.id_hidrologica";
                $db = new DB();
                $db=$db->connection('mapa_soluciones');
                $stmt = $db->prepare($sql2); 
                $stmt->execute();
                $stmt = $stmt->get_result();
                $resultado2 = $stmt->fetch_all(MYSQLI_ASSOC);

                for ($i=0; $i < count($resultado); $i++) {

                    if (!$resultado[$i]) {
                         $resultado[$i]["proyectosFinalizados"]= 0;
                    }

                    for ($x=0; $x < count($resultado2); $x++) { 
                        if ($resultado[$i]["hidrologica"] === $resultado2[$x]["hidrologica"]) {
                            $resultado[$i]["proyectosFinalizados"]= $resultado2[$x]["finalizados"];
                        }
                    }
                }

                return $response->withJson($resultado);

                

            }
            catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
            
                            
               
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
     });


     $app->put('/api/actualizacion/acciones/especificas/{id_accion}/{valor}', function (Request $request, Response $response){
        $id_accion = $request->getAttribute('id_accion') + 0;
        $valor = $request->getAttribute('valor') + 0;

       $check = array($id_accion, $valor);

        
        $contador = 0;

        for ($i=0; $i < count($check) ; $i++) { 
            if (!isset($check[$i])) {
                    $contador++;
                }
            }
                
                if ($contador === 0){
                    $registro = new Registro(0,$id_accion);
                    return $registro->actualizacion($valor);
                }else{
                    return 'Hay variables que no estan definida';
                }

        


     });

     
     $app->post('/api/registro/proyetos', function (Request $request, Response $response){
        $body = json_decode($request->getBody());
        $body = json_decode($body->body);
            $nombre_datos = $body->{'datos'}->{'nombre_datos'};
            $id_tipo_solucion_datos =$body->{'datos'}->{'id_tipo_solucion_datos'};           
            $descripcion_datos = $body->{'datos'}->{'descripcion_datos'};
            $accion_general_datos = $body->{'datos'}->{'accion_general'};
            
            $acciones_especificas = $body->{'acciones_especificas'};
            
            $obra = $body->{'obra'};

            $coordenadas_sector = $body->{'coordenadas_sector'};                      
           
            $lapso_estimado_inicio = $body->{'lapso_estimado_inicio'}; 
            $lapso_estimado_culminacion = $body->{'lapso_estimado_culminacion'};
            
            $ciclo_inicial =$body->{'ciclo_inicial'};
            $opcion_ciclo_inicial = $body->{'opcion_ciclo_inicial'};

            if ($ciclo_inicial < 4 && $opcion_ciclo_inicial === "dias") {
                $id_estado_proyecto = 1;
            }else if ($ciclo_inicial > 3 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial < 45 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial > 0 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial < 7 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial === 1 && $opcion_ciclo_inicial === "meses") {
                $id_estado_proyecto = 2;
            }else if ($ciclo_inicial > 44 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial > 6 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial > 1 && $opcion_ciclo_inicial === "meses") {
               $id_estado_proyecto = 3;
            }else {
                $id_estado_proyecto = 3;
            }
                  
            $ejecucion_bolivares =  $body->{'ejecucion_bolivares'};
            $ejecucion_euros = $body->{'ejecucion_euros'};
            $ejecucion_dolares =$body->{'ejecucion_dolares'};
            $ejecucion_rublos = $body->{'ejecucion_rublos'};
            
            $inversion_bolivares = $body->{'inversion_bolivares'};
            $inversion_euros = $body->{'inversion_euros'};
            $inversion_dolares = $body->{'inversion_dolares'};
            $inversion_rublos = $body->{'inversion_rublos'};            
            
            $poblacion_inicial = $body->{'poblacion_inicial'};     
            
            $lps_inicial =$body->{'lps_inicial'};       

            
           
            $id_hidrologica = $body->{'id_hidrologica'};
            $id_estado = $body->{'id_estado'};
            $id_municipio = $body->{'id_municipio'};
            $id_parroquia = $body->{'id_parroquia'};
            $id_estatus = $body->{'id_estatus'};        
            
            $datos = array($nombre_datos , $id_tipo_solucion_datos , $descripcion_datos , $accion_general_datos);
            $sector = array( $coordenadas_sector , );
            $lapso = array($lapso_estimado_inicio , $lapso_estimado_culminacion);
            $ciclos = array( $ciclo_inicial , $opcion_ciclo_inicial );
            $ejecucion_financiera = array($ejecucion_bolivares , $ejecucion_euros , $ejecucion_dolares , $ejecucion_rublos);
            $inversion = array($inversion_bolivares ,  $inversion_euros , $inversion_dolares , $inversion_rublos);
            $proyecto = array( $id_hidrologica , $id_estado , $id_municipio , $id_parroquia , $id_estatus , $id_estado_proyecto);

            $check = array($nombre_datos , $id_tipo_solucion_datos , $descripcion_datos , $accion_general_datos , $coordenadas_sector , $lapso_estimado_inicio , $lapso_estimado_culminacion, $ciclo_inicial , $opcion_ciclo_inicial, $ejecucion_bolivares , $ejecucion_euros , $ejecucion_dolares , $ejecucion_rublos , $inversion_bolivares ,  $inversion_euros , $inversion_dolares , $inversion_rublos , $id_hidrologica , $id_estado , $id_municipio , $id_parroquia , $id_estatus , $id_estado_proyecto, $acciones_especificas , $obra ,$poblacion_inicial , $lps_inicial);
            $contador = 0;
    
            for ($i=0; $i < count($check) ; $i++) { 
                if (!isset($check[$i])) {
                        $contador++;
                    }
                }
                    
                    if ($contador === 0){
                        $registro = new Registro();
                        return $registro->crearProyectos($datos , $acciones_especificas , $obra , $sector, $lapso , $ciclos , $ejecucion_financiera , $inversion , $poblacion_inicial , $lps_inicial , $proyecto);
                
                    }else{
                        return 'Hay variables que no estan definida';
                    }



     });


     $app->put('/api/actualizacion/final/proyetos', function (Request $request, Response $response){
        $body = json_decode($request->getBody());


        
        $id_lapso = $body->{'id_lapso'};        
        $lapso_culminacion_final = $body->{'lapso_culminacion_final'};        
        $lapso_culminación_inicio = $body->{'lapso_culminacion_inicio'};

        $ciclo_final = $body->{'ciclo_final'};        
        $opcion_ciclo_final = $body->{'opcion_ciclo_final'};
        $id_ciclo = $body->{'id_ciclo'};        

        $ejecucion_bolivares_final = $body->{'ejecucion_bolivares_final'};
        $ejecucion_euros_final = $body->{'ejecucion_euros_final'};
        $ejecucion_dolares_final = $body->{'ejecucion_dolares_final'};
        $ejecucion_rublos_final = $body->{'ejecucion_rublos_final'};
        $id_ejecucion_financiera = $body->{'id_ejecucion_financiera'};

        
        $poblacion_final = $body->{'poblacion_final'};
        $id_poblacion = $body->{'id_ejecucion_financiera'};
        
        $lps_final = $body->{'poblacion_final'};
        $id_lps = $body->{'id_ejecucion_financiera'};

        $id_estatus = $body->{'id_estatus'};
        $id_estado_proyecto = $body->{'id_estado_proyecto'};
        $id_proyecto = $body->{'id_proyecto'};
        


        $lapso = array($id_lapso , $lapso_culminacion_final , $lapso_culminación_inicio);
        $ciclos = array($ciclo_final , $opcion_ciclo_final , $id_ciclo);
        $ejecucion_financiera = array($ejecucion_bolivares_final , $ejecucion_euros_final , $ejecucion_dolares_final , $ejecucion_rublos_final , $id_ejecucion_financiera);
        $poblacion = array($poblacion_final , $id_poblacion);
        $lps = array($lps_final , $id_lps);
        $proyectos = array($id_estatus , $id_estado_proyecto, $id_proyecto);
        
        $check = array($id_lapso , $lapso_culminacion_final , $lapso_culminación_inicio,$ciclo_final , $opcion_ciclo_final , $id_ciclo,$ejecucion_bolivares_final , $ejecucion_euros_final , $ejecucion_dolares_final , $ejecucion_rublos_final , $id_ejecucion_financiera, $poblacion_final , $id_poblacion,$lps_final , $id_lps, $id_estatus , $id_estado_proyecto, $id_proyecto);
        $contador = 0;
    
            for ($i=0; $i < count($check) ; $i++) { 
                if (!isset($check[$i])) {
                        $contador++;
                    }
                }
                    
                    if ($contador === 0){
                        $registro = new Registro();
                        return $registro->actualizacionFinal($lapso , $ciclos , $ejecucion_financiera , $poblacion , $lps, $proyectos);
            
                    }else{
                        return 'Hay variables que no estan definida';
                    }


     });

     $app->get('/api/calculo/porcentaje/acciones', function (Request $request, Response $response) { 
        $body = json_decode($request->getBody());
            $id_datos = $body->{'id_datos'};

            $estadisticas = new Proyecto();
            return $estadisticas->porcentajeAcciones($id_datos);
            
          });
     