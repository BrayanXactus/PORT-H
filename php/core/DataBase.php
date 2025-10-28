<?php

class DataBase 
{
    public $pdo = null;
    public $sSql = null;
    public $stm = null;
    public $oSistema = null;
    public $sBaseDatos = null;
    public $err_msg = array();
    public $aList = array();
    public $aGroupList = array();
    public $aExecute = array();
    public $aDataLog = array();
    public $iLastInsertId = null;
    private $aConsultasTransaccion = array();
    private $aValoresTransaccion = array();

    public function __construct() 
    {
        try {
            $hostname = DB_HOST;
            $dbname = DB_DATA;
            $username = DB_USER;
            $password = DB_PASSWORD;
    
            $connectionOptions = array(
                "Database" => $dbname,
                "Uid" => $username,
                "PWD" => $password,
                "CharacterSet" => "UTF-8"
            );
    
            $this->pdo = new PDO("sqlsrv:Server=$hostname;Database=$dbname", $username, $password, $connectionOptions);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            print_r($e);
        } catch (Throwable $ex) {
            print_r($ex);
        }
    }

    public function execQuery(): bool 
    {
        try {
            $this->stm = $this->pdo->prepare($this->sSql);
            $this->stm->execute($this->aExecute);
            return true;
        } catch (PDOException $e) {
            print_r($e);
        } catch (Throwable $ex) {
            print_r($ex);
        }

        return false;
    }

    public function insertSingle(string $sTable, array $aData, bool $bRecalcularLastInsert = true): bool 
    {
        $this->aExecute = array_values($aData);
        $this->sSql = "INSERT INTO dbo." . $sTable . " (" . implode(', ', array_keys($aData)) . ") VALUES " . $this->argsInsert(count($aData), 1) . ";";
       
        $bExec = $this->execQuery();
        
        if ($bExec) {
            $this->iLastInsertId = $bRecalcularLastInsert ? (int) $this->pdo->lastInsertId() : $this->iLastInsertId;
        }
        
        return $bExec;
    }

    public function iniciarInsertMultiple(string $sTable, array $aRows)
    {
        $aDataInsertar = array();
        $iCantidadFilas = 0;

        foreach ($aRows as $i => $aData) {
            if ($iCantidadFilas === 500) {
                $this->insertMultiple($sTable, $aDataInsertar);
                $aDataInsertar = array();
                $iCantidadFilas = 0;
            }

            $aDataInsertar[] = $aData;
            $iCantidadFilas++;
        }

        if ($iCantidadFilas > 0) {
            $this->insertMultiple($sTable, $aDataInsertar);
        }
    }

    public function insertMultiple(string $sTable, array $aRows): bool 
    {
        try {
            $this->aExecute = array();
            $aCampos = array_keys($aRows[0]);

            $this->sSql = "INSERT INTO dbo." . $sTable . " (" . implode(', ', $aCampos) . ") VALUES " . $this->argsInsert(count($aCampos), count($aRows)) . ";";
            foreach ($aRows as $i => $aData) {
                $this->aExecute = array_merge_recursive($this->aExecute, array_values($this->parsingValuesQuery($aData)));
            }

            return $this->execQuery();
        } catch (PDOException $e) {
            print($e);
        } catch (Throwable $ex) {
            print($ex);
        }

        return false;
    }

    public function updateSingle(string $sTable, array $aData, array $aWhere = array(), array $aWhereIn = array(), array $aWhereNotIn = array()): bool 
    {
        $this->sSql = "UPDATE dbo." . $sTable . " SET " . implode(" = ?, ", array_keys($aData)) . " = ? WHERE 1 = 1";

        $aValoresTotal = array_values($aData);

        if (count($aWhere) > 0) {
            $this->sSql .= " AND " . implode(" = ? AND ", array_keys($aWhere)) . " = ?";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aWhere));
        }

        foreach ($aWhereIn as $sCampo => $aValores) {
            $this->sSql .= " AND " . $sCampo . " IN (" . implode(', ', array_fill(0, count($aValores), '?')) . ")";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aValores));
        }

        foreach ($aWhereNotIn as $sCampo => $aValores) {
            $this->sSql .= " AND " . $sCampo . " NOT IN (" . implode(', ', array_fill(0, count($aValores), '?')) . ")";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aValores));
        }

        $this->aExecute = $aValoresTotal;
        $bExec = $this->execQuery();
        
        return $bExec;
    }

    public function deleteSingle(string $sTable, array $aWhere, array $aWhereIn = array(), array $aWhereNotIn = array()): bool 
    {
        $this->sSql = "DELETE FROM dbo." . $sTable . " WHERE 1 = 1";

        $aValoresTotal = array();

        if (count($aWhere) > 0) {
            $this->sSql .= " AND " . implode(" = ? AND ", array_keys($aWhere)) . " = ?";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aWhere));
        }

        foreach ($aWhereIn as $sCampo => $aValores) {
            $this->sSql .= " AND " . $sCampo . " IN (" . implode(', ', array_fill(0, count($aValores), '?')) . ")";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aValores));
        }

        foreach ($aWhereNotIn as $sCampo => $aValores) {
            $this->sSql .= " AND " . $sCampo . " NOT IN (" . implode(', ', array_fill(0, count($aValores), '?')) . ")";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aValores));
        }

        $this->aExecute = $aValoresTotal;
        $bExec = $this->execQuery();
        
        return $bExec;
    }

    public function countIdsAND(string $sTable, string $sCampoId, array $aWhere = array(), array $aWhereIn = array(), int $iIdExcluir = 0) : int 
    {
        $this->sSql = "SELECT COUNT(" . $sCampoId . ") AS 'cantidad' FROM dbo." . $sTable . " WHERE 1 = 1";
        $aValoresTotal = array();

        if (count($aWhere) > 0) {
            $this->sSql .= " AND " . implode(" = ? AND ", array_keys($aWhere)) . " = ?";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aWhere));
        }

        foreach ($aWhereIn as $sCampo => $aValores) {
            $this->sSql .= " AND " . $sCampo . " IN (" . implode(', ', array_fill(0, count($aValores), '?')) . ")";
            $aValoresTotal = array_merge_recursive(array_values($aValoresTotal), array_values($aValores));
        }
        
        if ($iIdExcluir !== 0) {
            $this->sSql .= " AND " . $sCampoId . " != ? ";
            $aValoresTotal[] = $iIdExcluir;
        }

        $this->aExecute = $aValoresTotal;

        $this->execQuery();

        $iCantidad = 0;

        foreach ($this->stm->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $oInfoCantidad = (object) $r;
            $iCantidad = $oInfoCantidad->cantidad;
        }

        return $iCantidad;
    }

    public function parsingValuesQuery(array $aDataParsing): array 
    {
        try {
            $aParsing = array();

            foreach ($aDataParsing as $key => $value) {
                $v = is_null($value) ? null : (
                    is_numeric($value) ? (strpos((string) $value, '.') === false ? (int) $value : (double) $value) : (
                        is_string($value) ? (strlen($value) > 0 ? $value : null) : (
                            is_object($value) ? (is_a($value, 'DateTime') ? $value->format('Y-m-d H:i:s') : $value) : null
                        )
                    )
                );

                $aParsing[$key] = $v;
            }

            return $aParsing;
        } catch (Exception $e) {
            return array();
        }
    }

    public function argsInsert(int $iColumnLength, int $iRowLength): string 
    {
        $iLength = $iRowLength * $iColumnLength;

        return implode(', ', array_map(
            function ($el) {
                return '(' . implode(', ', $el) . ')';
            },
            array_chunk(array_fill(0, $iLength, '?'), $iColumnLength)
        ));
    }

    public function ultimoIdInsertado(): int 
    {
        return $this->iLastInsertId;
    }

    public function restarFechas(string $sFecha, int $iDia = 0, int $iMes = 0, $iAnio = 0, int $iHora = 0, int $iMinuto = 0, int $iSegundos = 0) 
    {
        $date = new DateTime(date('Y-m-d H:i:s', strtotime($sFecha)));
        $sNuevaFecha = $date->format("Y-m-d H:i:s");

        if ($iSegundos > 0) $date->modify("-$iSegundos second");
        if ($iMinuto > 0)   $date->modify("-$iMinuto minute");
        if ($iHora > 0)     $date->modify("-$iHora hour");
        if ($iDia > 0)      $date->modify("-$iDia days");
        if ($iMes > 0)      $date->modify("-$iMes months");
        if ($iAnio > 0)     $date->modify("-$iAnio years");

        return $date->format("Y-m-d H:i:s");
    }
}
