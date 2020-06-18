<?php
        class Registro {

            private $id_proyecto;
            private $accion;

            function __construct($id_proyecto=null , $accion=null){
                $this->id_proyecto=$id_proyecto;
                $this->accion=$accion;
            }

            function actualizacion($observacion , $valor){

                $sql = "UPDATE acciones_especificas SET observacion=?,valor=? WHERE id_accion_especifica = ?";
               
                try {
                    $db = new DB();
                    $db=$db->connection('mapa_soluciones');
                    $stmt = $db->prepare($sql); 
                    $stmt->bind_param("sii", $observacion , $valor , $this->accion);
                    $stmt->execute();
                    $stmt = $stmt->get_result();

                    return "Se ha actualizado";
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


            function crearProyectos($datos , $acciones_especificas , $obras , $sector, $lapso){
               
                $sql = "INSERT INTO datos (id_datos, nombre, id_tipo_solucion, descripcion, accion_general) VALUES (NULL, ? , ? , ? , ?)";

                try {
                    $db = new DB();
                    $db=$db->connection('mapa_soluciones');
                    $stmt = $db->prepare($sql); 
                    $stmt->bind_param("siss", $datos[0] , $datos[1] , $datos[2] , $datos[3]);
                    $stmt->execute();
                    $stmt = $stmt->get_result();

                        if ($stmt) {

                            $stmt = $stmt->{"insert_id"};
                            $sql = "INSERT INTO acciones_especificas (id_accion_especifica, accion_especifica, observacion, id_datos, valor) VALUES (NULL, ? , ? , ? , ? )";
                            $db = new DB();
                            $db=$db->connection('mapa_soluciones');
                            $stmt = $db->prepare($sql); 
                            $stmt->bind_param("ssii", $acciones_especificas[0] , $acciones_especificas[1] , $id_datos , $acciones_especificas[2]);
                            $stmt->execute();
                            $stmt = $stmt->get_result();

                            if ($stmt) {
                                $stmt = $stmt->{"insert_id"};
                                $sql = "INSERT INTO acciones_especificas (id_accion_especifica, accion_especifica, observacion, id_datos, valor) VALUES (NULL, ? , ? , ? , 0 )";
                                $db = new DB();
                                $db=$db->connection('mapa_soluciones');
                                $stmt = $db->prepare($sql); 
                                $stmt->bind_param("ssii", $acciones_especificas[0] , $acciones_especificas[1] , $id_datos);
                                $stmt->execute();
                                $stmt = $stmt->get_result();

                                if ($stmt) {
                                    $stmt = $stmt->{"insert_id"};
                                    $sql = "INSERT INTO obras (id_obra, coordenadas) VALUES (NULL, ?)";
                                    $db = new DB();
                                    $db=$db->connection('mapa_soluciones');
                                    $stmt = $db->prepare($sql); 
                                    $stmt->bind_param("s", $obras);
                                    $stmt->execute();
                                    $stmt = $stmt->get_result();

                                    if ($stmt) {
                                        $stmt = $stmt->{"insert_id"};
                                        $sql = "INSERT INTO sector (id_sector, coordenadas, nombre) VALUES (NULL, ?, ?)";
                                        $db = new DB();
                                        $db=$db->connection('mapa_soluciones');
                                        $stmt = $db->prepare($sql); 
                                        $stmt->bind_param("ss", $sector[0] , $sector[1]);
                                        $stmt->execute();
                                        $stmt = $stmt->get_result();

                                        if ($stmt) { 
                                            $stmt = $stmt->{"insert_id"};
                                            $sql = "INSERT INTO lapso (id_lapso, lapso_estimado_inicio, lapso_estimado_culminacion, lapso_culminación_inicio, lapso_culminación_final) VALUES (NULL, ? , ? , 0 , 0 );";
                                            $db = new DB();
                                            $db=$db->connection('mapa_soluciones');
                                            $stmt = $db->prepare($sql); 
                                            $stmt->bind_param("ss", $lapso[0] , $lapso[1] );
                                            $stmt->execute();
                                            $stmt = $stmt->get_result();
                                    }
                                }
                            }

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

                
            }
            }

        



?>