<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = new \Slim\App;

require __DIR__ . '/../class/auth.php';
require __DIR__ . '/../class/estadisticas.php';
require __DIR__ . '/../class/classRegistros.php';
require __DIR__ . '/../config/db.php';

use \Firebase\JWT\JWT;

//******************Agregar clientes Post***************//

$app->post('/authenticate', function (Request $request, Response $response) {
    $body = json_decode($request->getBody());

    $sql = "SELECT `usuarios`.*
            FROM `usuarios`";
    $db = new DB();
    $resultado = $db->consultaSinParametros('usuarios_m_soluciones', $sql);
    
    
    
    foreach ($resultado[0] as $key => $user) {
    if ($user['nick'] == 'ely10' && $user['pass'] == 1) {
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
            var_dump($token_from_db);
            $db = null;
            if ($token_from_db) {
                return $response->withJson([
                "Token" => $token_from_db->token,
                "User_render" =>$current_user['id_rol']
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



$app->get('/api/estadistica/situacion/servicio', function (Request $request, Response $response) { /*grafico abajo, centro, donde se muestra dos valores del estado de servicio global*/ 
    
        $sql  ="SELECT `situaciones de servicio`.`situacion_de_servicio` , COUNT(proyectos.id_estado_proyecto) as cantidad 
                FROM proyectos 
                LEFT JOIN `situaciones de servicio` ON proyectos.id_estado_proyecto = `situaciones de servicio`.`id_situacion_de_servicio`
                WHERE proyectos.id_estado_proyecto IN (1, 2, 3) 
                GROUP BY `situaciones de servicio`.`situacion_de_servicio`";

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


     $app->put('/api/actualizacion/acciones/especificas/{id_accion}/{valor},{observacion}', function (Request $request, Response $response){
        $id_accion = $request->getAttribute('id_accion') + 0;
        $observacion = $request->getAttribute('observacion');
        $valor = $request->getAttribute('valor') + 0;

        $registro = new Registro(0,$id_accion);

        return $registro->actualizacion($observacion , $valor);

        


     });
     $app->post('/api/registro/proyetos', function (Request $request, Response $response){
        $body = json_decode($request->getBody());

            $nombre_datos = $body->{'datos'}->{'nombre_datos'};
            $id_tipo_solucion_datos =$body->{'datos'}->{'id_tipo_solucion_datos'};           
            $descripcion_datos = $body->{'datos'}->{'descripcion_datos'};
            $accion_general_datos = $body->{'datos'}->{'accion_general'};

            $accion_especifica = $body->{'accion_especifica'};
            $observacion =$body->{'observacion'};
            
            $obra = $body->{'obra'};

            $coordenadas_sector = $body->{'coordenadas_sector'};
            $nombre_sector = $body->{'nombre_sector'};           
           
            $lapso_estimado_inicio = $body->{'lapso_estimado_inicio'}; 
            $lapso_estimado_culminacion = $body->{'lapso_estimado_culminacion'};
            
            $ciclo_inicial =$body->{'ciclo_inicial'};
            $opcion_ciclo_inicial = $body->{'opcion_ciclo_inicial'};//---
            
            $ejecucion_bolivares =  $body->{'ejecucion_bolivares'};
            $ejecucion_euros = $body->{'ejecucion_euros'};
            $ejecucion_dolares =$body->{'ejecucion_dolares'};
            $ejecucion_rublos = $body->{'ejecucion_rublos'};
            
            $inversion_bolivares = $body->{'inversion_bolivares'};
            $inversion_euros = $body->{'inversion_euros'};
            $inversion_dolares = $body->{'inversion_dolares'};
            $inversion_rublos = $body->{'inversion_rublos'};            
            
            $poblacion_inicial = $body->{'poblacion_inicial'};    //---     
            
            $lps_inicial =$body->{'lps_inicial'};//---

            $nombre_proyecto = $body->{'nombre_proyecto'};
            $descripcion_proyecto = $body->{'descripcion_proyecto'};
            $id_hidrologica = $body->{'id_hidrologica'};
            $id_estado = $body->{'id_estado'};
            $id_municipio = $body->{'id_municipio'};
            $id_parroquia = $body->{'id_parroquia'};
            $id_estatus = $body->{'id_estatus'};
            $id_estado_proyecto = $body->{'id_estado_proyecto'};
            

            $datos = array($nombre_datos , $id_tipo_solucion_datos , $descripcion_datos , $accion_general_datos );
            $acciones_especificas = array($accion_especifica , $observacion);
            $sector = array( $coordenadas_sector , $nombre_sector);
            $lapso = array($lapso_estimado_inicio , $lapso_estimado_culminacion);
            $ciclos = array( $ciclo_inicial , $opcion_ciclo_inicial );
            $ejecucion_financiera = array($ejecucion_bolivares , $ejecucion_euros , $ejecucion_dolares , $ejecucion_rublos);
            $inversion = array($inversion_bolivares ,  $inversion_euros , $inversion_dolares , $inversion_rublos);
            $proyecto = array( $nombre_proyecto , $descripcion_proyecto , $id_hidrologica , $id_estado , $id_municipio , $id_parroquia , $id_estatus , $id_estado_proyecto);

        
        $registro = new Registro();
        return $registro->crearProyectos($datos , $acciones_especificas , $obra , $sector, $lapso , $ciclos , $ejecucion_financiera , $inversion , $poblacion_inicial , $lps_inicial , $proyecto);


     });

