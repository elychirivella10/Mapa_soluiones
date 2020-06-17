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


            function crearProyectos($datos){
                var_dump($datos);

                
            }
            }

        



?>