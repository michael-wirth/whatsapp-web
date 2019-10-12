<?php
 namespace Doctrine\DBAL\Platforms; use Doctrine\DBAL\Schema\ForeignKeyConstraint; use Doctrine\DBAL\Schema\Identifier; use Doctrine\DBAL\Schema\Index; use Doctrine\DBAL\Schema\Sequence; use Doctrine\DBAL\Schema\Table; use Doctrine\DBAL\Schema\TableDiff; use Doctrine\DBAL\DBALException; use Doctrine\DBAL\Types\BinaryType; class OraclePlatform extends AbstractPlatform { static public function assertValidIdentifier($identifier) { if ( ! preg_match('(^(([a-zA-Z]{1}[a-zA-Z0-9_$#]{0,})|("[^"]+"))$)', $identifier)) { throw new DBALException("Invalid Oracle identifier"); } } public function getSubstringExpression($value, $position, $length = null) { if ($length !== null) { return "SUBSTR($value, $position, $length)"; } return "SUBSTR($value, $position)"; } public function getNowExpression($type = 'timestamp') { switch ($type) { case 'date': case 'time': case 'timestamp': default: return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')'; } } public function getLocateExpression($str, $substr, $startPos = false) { if ($startPos == false) { return 'INSTR('.$str.', '.$substr.')'; } return 'INSTR('.$str.', '.$substr.', '.$startPos.')'; } public function getGuidExpression() { return 'SYS_GUID()'; } protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit) { switch ($unit) { case self::DATE_INTERVAL_UNIT_MONTH: case self::DATE_INTERVAL_UNIT_QUARTER: case self::DATE_INTERVAL_UNIT_YEAR: switch ($unit) { case self::DATE_INTERVAL_UNIT_QUARTER: $interval *= 3; break; case self::DATE_INTERVAL_UNIT_YEAR: $interval *= 12; break; } return 'ADD_MONTHS(' . $date . ', ' . $operator . $interval . ')'; default: $calculationClause = ''; switch ($unit) { case self::DATE_INTERVAL_UNIT_SECOND: $calculationClause = '/24/60/60'; break; case self::DATE_INTERVAL_UNIT_MINUTE: $calculationClause = '/24/60'; break; case self::DATE_INTERVAL_UNIT_HOUR: $calculationClause = '/24'; break; case self::DATE_INTERVAL_UNIT_WEEK: $calculationClause = '*7'; break; } return '(' . $date . $operator . $interval . $calculationClause . ')'; } } public function getDateDiffExpression($date1, $date2) { return "TRUNC(TO_NUMBER(SUBSTR((" . $date1 . "-" . $date2 . "), 1, INSTR(" . $date1 . "-" . $date2 .", ' '))))"; } public function getBitAndComparisonExpression($value1, $value2) { return 'BITAND('.$value1 . ', ' . $value2 . ')'; } public function getBitOrComparisonExpression($value1, $value2) { return '(' . $value1 . '-' . $this->getBitAndComparisonExpression($value1, $value2) . '+' . $value2 . ')'; } public function getCreateSequenceSQL(Sequence $sequence) { return 'CREATE SEQUENCE ' . $sequence->getQuotedName($this) . ' START WITH ' . $sequence->getInitialValue() . ' MINVALUE ' . $sequence->getInitialValue() . ' INCREMENT BY ' . $sequence->getAllocationSize() . $this->getSequenceCacheSQL($sequence); } public function getAlterSequenceSQL(Sequence $sequence) { return 'ALTER SEQUENCE ' . $sequence->getQuotedName($this) . ' INCREMENT BY ' . $sequence->getAllocationSize() . $this->getSequenceCacheSQL($sequence); } private function getSequenceCacheSQL(Sequence $sequence) { if ($sequence->getCache() === 0) { return ' NOCACHE'; } else if ($sequence->getCache() === 1) { return ' NOCACHE'; } else if ($sequence->getCache() > 1) { return ' CACHE ' . $sequence->getCache(); } return ''; } public function getSequenceNextValSQL($sequenceName) { return 'SELECT ' . $sequenceName . '.nextval FROM DUAL'; } public function getSetTransactionIsolationSQL($level) { return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level); } protected function _getTransactionIsolationLevelSQL($level) { switch ($level) { case \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED: return 'READ UNCOMMITTED'; case \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED: return 'READ COMMITTED'; case \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ: case \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE: return 'SERIALIZABLE'; default: return parent::_getTransactionIsolationLevelSQL($level); } } public function getBooleanTypeDeclarationSQL(array $field) { return 'NUMBER(1)'; } public function getIntegerTypeDeclarationSQL(array $field) { return 'NUMBER(10)'; } public function getBigIntTypeDeclarationSQL(array $field) { return 'NUMBER(20)'; } public function getSmallIntTypeDeclarationSQL(array $field) { return 'NUMBER(5)'; } public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration) { return 'TIMESTAMP(0)'; } public function getDateTimeTzTypeDeclarationSQL(array $fieldDeclaration) { return 'TIMESTAMP(0) WITH TIME ZONE'; } public function getDateTypeDeclarationSQL(array $fieldDeclaration) { return 'DATE'; } public function getTimeTypeDeclarationSQL(array $fieldDeclaration) { return 'DATE'; } protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef) { return ''; } protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed) { return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(2000)') : ($length ? 'VARCHAR2(' . $length . ')' : 'VARCHAR2(4000)'); } protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed) { return 'RAW(' . ($length ?: $this->getBinaryMaxLength()) . ')'; } public function getBinaryMaxLength() { return 2000; } public function getClobTypeDeclarationSQL(array $field) { return 'CLOB'; } public function getListDatabasesSQL() { return 'SELECT username FROM all_users'; } public function getListSequencesSQL($database) { $database = $this->normalizeIdentifier($database); $database = $this->quoteStringLiteral($database->getName()); return "SELECT sequence_name, min_value, increment_by FROM sys.all_sequences ". "WHERE SEQUENCE_OWNER = " . $database; } protected function _getCreateTableSQL($table, array $columns, array $options = array()) { $indexes = isset($options['indexes']) ? $options['indexes'] : array(); $options['indexes'] = array(); $sql = parent::_getCreateTableSQL($table, $columns, $options); foreach ($columns as $name => $column) { if (isset($column['sequence'])) { $sql[] = $this->getCreateSequenceSQL($column['sequence'], 1); } if (isset($column['autoincrement']) && $column['autoincrement'] || (isset($column['autoinc']) && $column['autoinc'])) { $sql = array_merge($sql, $this->getCreateAutoincrementSql($name, $table)); } } if (isset($indexes) && ! empty($indexes)) { foreach ($indexes as $index) { $sql[] = $this->getCreateIndexSQL($index, $table); } } return $sql; } public function getListTableIndexesSQL($table, $currentDatabase = null) { $table = $this->normalizeIdentifier($table); $table = $this->quoteStringLiteral($table->getName()); return "SELECT uind_col.index_name AS name,
                       (
                           SELECT uind.index_type
                           FROM   user_indexes uind
                           WHERE  uind.index_name = uind_col.index_name
                       ) AS type,
                       decode(
                           (
                               SELECT uind.uniqueness
                               FROM   user_indexes uind
                               WHERE  uind.index_name = uind_col.index_name
                           ),
                           'NONUNIQUE',
                           0,
                           'UNIQUE',
                           1
                       ) AS is_unique,
                       uind_col.column_name AS column_name,
                       uind_col.column_position AS column_pos,
                       (
                           SELECT ucon.constraint_type
                           FROM   user_constraints ucon
                           WHERE  ucon.index_name = uind_col.index_name
                       ) AS is_primary
             FROM      user_ind_columns uind_col
             WHERE     uind_col.table_name = " . $table . "
             ORDER BY  uind_col.column_position ASC"; } public function getListTablesSQL() { return 'SELECT * FROM sys.user_tables'; } public function getListViewsSQL($database) { return 'SELECT view_name, text FROM sys.user_views'; } public function getCreateViewSQL($name, $sql) { return 'CREATE VIEW ' . $name . ' AS ' . $sql; } public function getDropViewSQL($name) { return 'DROP VIEW '. $name; } public function getCreateAutoincrementSql($name, $table, $start = 1) { $tableIdentifier = $this->normalizeIdentifier($table); $quotedTableName = $tableIdentifier->getQuotedName($this); $unquotedTableName = $tableIdentifier->getName(); $nameIdentifier = $this->normalizeIdentifier($name); $quotedName = $nameIdentifier->getQuotedName($this); $unquotedName = $nameIdentifier->getName(); $sql = array(); $autoincrementIdentifierName = $this->getAutoincrementIdentifierName($tableIdentifier); $idx = new Index($autoincrementIdentifierName, array($quotedName), true, true); $sql[] = 'DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = \'' . $unquotedTableName . '\' AND CONSTRAINT_TYPE = \'P\';
  IF constraints_Count = 0 OR constraints_Count = \'\' THEN
    EXECUTE IMMEDIATE \''.$this->getCreateConstraintSQL($idx, $quotedTableName).'\';
  END IF;
END;'; $sequenceName = $this->getIdentitySequenceName( $tableIdentifier->isQuoted() ? $quotedTableName : $unquotedTableName, $nameIdentifier->isQuoted() ? $quotedName : $unquotedName ); $sequence = new Sequence($sequenceName, $start); $sql[] = $this->getCreateSequenceSQL($sequence); $sql[] = 'CREATE TRIGGER ' . $autoincrementIdentifierName . '
   BEFORE INSERT
   ON ' . $quotedTableName . '
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT ' . $sequenceName . '.NEXTVAL INTO :NEW.' . $quotedName . ' FROM DUAL;
   IF (:NEW.' . $quotedName . ' IS NULL OR :NEW.'.$quotedName.' = 0) THEN
      SELECT ' . $sequenceName . '.NEXTVAL INTO :NEW.' . $quotedName . ' FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = \'' . $sequence->getName() . '\';
      SELECT :NEW.' . $quotedName . ' INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT ' . $sequenceName . '.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;'; return $sql; } public function getDropAutoincrementSql($table) { $table = $this->normalizeIdentifier($table); $autoincrementIdentifierName = $this->getAutoincrementIdentifierName($table); $identitySequenceName = $this->getIdentitySequenceName( $table->isQuoted() ? $table->getQuotedName($this) : $table->getName(), '' ); return array( 'DROP TRIGGER ' . $autoincrementIdentifierName, $this->getDropSequenceSQL($identitySequenceName), $this->getDropConstraintSQL($autoincrementIdentifierName, $table->getQuotedName($this)), ); } private function normalizeIdentifier($name) { $identifier = new Identifier($name); return $identifier->isQuoted() ? $identifier : new Identifier(strtoupper($name)); } private function getAutoincrementIdentifierName(Identifier $table) { $identifierName = $table->getName() . '_AI_PK'; return $table->isQuoted() ? $this->quoteSingleIdentifier($identifierName) : $identifierName; } public function getListTableForeignKeysSQL($table) { $table = $this->normalizeIdentifier($table); $table = $this->quoteStringLiteral($table->getName()); return "SELECT alc.constraint_name,
          alc.DELETE_RULE,
          alc.search_condition,
          cols.column_name \"local_column\",
          cols.position,
          r_alc.table_name \"references_table\",
          r_cols.column_name \"foreign_column\"
     FROM user_cons_columns cols
LEFT JOIN user_constraints alc
       ON alc.constraint_name = cols.constraint_name
LEFT JOIN user_constraints r_alc
       ON alc.r_constraint_name = r_alc.constraint_name
LEFT JOIN user_cons_columns r_cols
       ON r_alc.constraint_name = r_cols.constraint_name
      AND cols.position = r_cols.position
    WHERE alc.constraint_name = cols.constraint_name
      AND alc.constraint_type = 'R'
      AND alc.table_name = " . $table . "
    ORDER BY cols.constraint_name ASC, cols.position ASC"; } public function getListTableConstraintsSQL($table) { $table = $this->normalizeIdentifier($table); $table = $this->quoteStringLiteral($table->getName()); return "SELECT * FROM user_constraints WHERE table_name = " . $table; } public function getListTableColumnsSQL($table, $database = null) { $table = $this->normalizeIdentifier($table); $table = $this->quoteStringLiteral($table->getName()); $tabColumnsTableName = "user_tab_columns"; $colCommentsTableName = "user_col_comments"; $tabColumnsOwnerCondition = ''; $colCommentsOwnerCondition = ''; if (null !== $database && '/' !== $database) { $database = $this->normalizeIdentifier($database); $database = $this->quoteStringLiteral($database->getName()); $tabColumnsTableName = "all_tab_columns"; $colCommentsTableName = "all_col_comments"; $tabColumnsOwnerCondition = "AND c.owner = " . $database; $colCommentsOwnerCondition = "AND d.OWNER = c.OWNER"; } return "SELECT   c.*,
                         (
                             SELECT d.comments
                             FROM   $colCommentsTableName d
                             WHERE  d.TABLE_NAME = c.TABLE_NAME " . $colCommentsOwnerCondition . "
                             AND    d.COLUMN_NAME = c.COLUMN_NAME
                         ) AS comments
                FROM     $tabColumnsTableName c
                WHERE    c.table_name = " . $table . " $tabColumnsOwnerCondition
                ORDER BY c.column_id"; } public function getDropSequenceSQL($sequence) { if ($sequence instanceof Sequence) { $sequence = $sequence->getQuotedName($this); } return 'DROP SEQUENCE ' . $sequence; } public function getDropForeignKeySQL($foreignKey, $table) { if (! $foreignKey instanceof ForeignKeyConstraint) { $foreignKey = new Identifier($foreignKey); } if (! $table instanceof Table) { $table = new Identifier($table); } $foreignKey = $foreignKey->getQuotedName($this); $table = $table->getQuotedName($this); return 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $foreignKey; } public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey) { $referentialAction = null; if ($foreignKey->hasOption('onDelete')) { $referentialAction = $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onDelete')); } return $referentialAction ? ' ON DELETE ' . $referentialAction : ''; } public function getForeignKeyReferentialActionSQL($action) { $action = strtoupper($action); switch ($action) { case 'RESTRICT': case 'NO ACTION': return ''; case 'CASCADE': case 'SET NULL': return $action; default: throw new \InvalidArgumentException('Invalid foreign key action: ' . $action); } } public function getDropDatabaseSQL($database) { return 'DROP USER ' . $database . ' CASCADE'; } public function getAlterTableSQL(TableDiff $diff) { $sql = array(); $commentsSQL = array(); $columnSql = array(); $fields = array(); foreach ($diff->addedColumns as $column) { if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) { continue; } $fields[] = $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray()); if ($comment = $this->getColumnComment($column)) { $commentsSQL[] = $this->getCommentOnColumnSQL( $diff->getName($this)->getQuotedName($this), $column->getQuotedName($this), $comment ); } } if (count($fields)) { $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ADD (' . implode(', ', $fields) . ')'; } $fields = array(); foreach ($diff->changedColumns as $columnDiff) { if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) { continue; } $column = $columnDiff->column; if ($column->getType() instanceof BinaryType && $columnDiff->hasChanged('fixed') && count($columnDiff->changedProperties) === 1 ) { continue; } $columnHasChangedComment = $columnDiff->hasChanged('comment'); if ( ! ($columnHasChangedComment && count($columnDiff->changedProperties) === 1)) { $columnInfo = $column->toArray(); if ( ! $columnDiff->hasChanged('notnull')) { unset($columnInfo['notnull']); } $fields[] = $column->getQuotedName($this) . $this->getColumnDeclarationSQL('', $columnInfo); } if ($columnHasChangedComment) { $commentsSQL[] = $this->getCommentOnColumnSQL( $diff->getName($this)->getQuotedName($this), $column->getQuotedName($this), $this->getColumnComment($column) ); } } if (count($fields)) { $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' MODIFY (' . implode(', ', $fields) . ')'; } foreach ($diff->renamedColumns as $oldColumnName => $column) { if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) { continue; } $oldColumnName = new Identifier($oldColumnName); $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' RENAME COLUMN ' . $oldColumnName->getQuotedName($this) .' TO ' . $column->getQuotedName($this); } $fields = array(); foreach ($diff->removedColumns as $column) { if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) { continue; } $fields[] = $column->getQuotedName($this); } if (count($fields)) { $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' DROP (' . implode(', ', $fields).')'; } $tableSql = array(); if ( ! $this->onSchemaAlterTable($diff, $tableSql)) { $sql = array_merge($sql, $commentsSQL); if ($diff->newName !== false) { $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' RENAME TO ' . $diff->getNewName()->getQuotedName($this); } $sql = array_merge( $this->getPreAlterTableIndexForeignKeySQL($diff), $sql, $this->getPostAlterTableIndexForeignKeySQL($diff) ); } return array_merge($sql, $tableSql, $columnSql); } public function getColumnDeclarationSQL($name, array $field) { if (isset($field['columnDefinition'])) { $columnDef = $this->getCustomTypeDeclarationSQL($field); } else { $default = $this->getDefaultValueDeclarationSQL($field); $notnull = ''; if (isset($field['notnull'])) { $notnull = $field['notnull'] ? ' NOT NULL' : ' NULL'; } $unique = (isset($field['unique']) && $field['unique']) ? ' ' . $this->getUniqueFieldDeclarationSQL() : ''; $check = (isset($field['check']) && $field['check']) ? ' ' . $field['check'] : ''; $typeDecl = $field['type']->getSqlDeclaration($field, $this); $columnDef = $typeDecl . $default . $notnull . $unique . $check; } return $name . ' ' . $columnDef; } protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName) { if (strpos($tableName, '.') !== false) { list($schema) = explode('.', $tableName); $oldIndexName = $schema . '.' . $oldIndexName; } return array('ALTER INDEX ' . $oldIndexName . ' RENAME TO ' . $index->getQuotedName($this)); } public function prefersSequences() { return true; } public function usesSequenceEmulatedIdentityColumns() { return true; } public function getIdentitySequenceName($tableName, $columnName) { $table = new Identifier($tableName); $identitySequenceName = $table->getName() . '_SEQ'; if ($table->isQuoted()) { $identitySequenceName = '"' . $identitySequenceName . '"'; } $identitySequenceIdentifier = $this->normalizeIdentifier($identitySequenceName); return $identitySequenceIdentifier->getQuotedName($this); } public function supportsCommentOnStatement() { return true; } public function getName() { return 'oracle'; } protected function doModifyLimitQuery($query, $limit, $offset = null) { if ($limit === null && $offset === null) { return $query; } if (preg_match('/^\s*SELECT/i', $query)) { if (!preg_match('/\sFROM\s/i', $query)) { $query .= " FROM dual"; } $columns = array('a.*'); if ($offset > 0) { $columns[] = 'ROWNUM AS doctrine_rownum'; } $query = sprintf('SELECT %s FROM (%s) a', implode(', ', $columns), $query); if ($limit !== null) { $query .= sprintf(' WHERE ROWNUM <= %d', $offset + $limit); } if ($offset > 0) { $query = sprintf('SELECT * FROM (%s) WHERE doctrine_rownum >= %d', $query, $offset + 1); } } return $query; } public function getSQLResultCasing($column) { return strtoupper($column); } public function getCreateTemporaryTableSnippetSQL() { return "CREATE GLOBAL TEMPORARY TABLE"; } public function getDateTimeTzFormatString() { return 'Y-m-d H:i:sP'; } public function getDateFormatString() { return 'Y-m-d 00:00:00'; } public function getTimeFormatString() { return '1900-01-01 H:i:s'; } public function fixSchemaElementName($schemaElementName) { if (strlen($schemaElementName) > 30) { return substr($schemaElementName, 0, 30); } return $schemaElementName; } public function getMaxIdentifierLength() { return 30; } public function supportsSequences() { return true; } public function supportsForeignKeyOnUpdate() { return false; } public function supportsReleaseSavepoints() { return false; } public function getTruncateTableSQL($tableName, $cascade = false) { $tableIdentifier = new Identifier($tableName); return 'TRUNCATE TABLE ' . $tableIdentifier->getQuotedName($this); } public function getDummySelectSQL() { return 'SELECT 1 FROM DUAL'; } protected function initializeDoctrineTypeMappings() { $this->doctrineTypeMapping = array( 'integer' => 'integer', 'number' => 'integer', 'pls_integer' => 'boolean', 'binary_integer' => 'boolean', 'varchar' => 'string', 'varchar2' => 'string', 'nvarchar2' => 'string', 'char' => 'string', 'nchar' => 'string', 'date' => 'date', 'timestamp' => 'datetime', 'timestamptz' => 'datetimetz', 'float' => 'float', 'binary_float' => 'float', 'binary_double' => 'float', 'long' => 'string', 'clob' => 'text', 'nclob' => 'text', 'raw' => 'binary', 'long raw' => 'blob', 'rowid' => 'string', 'urowid' => 'string', 'blob' => 'blob', ); } public function releaseSavePoint($savepoint) { return ''; } protected function getReservedKeywordsClass() { return 'Doctrine\DBAL\Platforms\Keywords\OracleKeywords'; } public function getBlobTypeDeclarationSQL(array $field) { return 'BLOB'; } public function quoteStringLiteral($str) { $str = str_replace('\\', '\\\\', $str); return parent::quoteStringLiteral($str); } } 