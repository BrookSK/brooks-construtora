USE brooks_obras;

SELECT 
    CONCAT(
        'CREATE TABLE `', TABLE_NAME, '` (\n',
        GROUP_CONCAT(
            CONCAT('  `', COLUMN_NAME, '` ', COLUMN_TYPE,
                   IF(IS_NULLABLE = 'NO', ' NOT NULL', ''),
                   IF(COLUMN_DEFAULT IS NOT NULL, CONCAT(' DEFAULT ', QUOTE(COLUMN_DEFAULT)), ''),
                   IF(EXTRA != '', CONCAT(' ', EXTRA), '')
            )
            ORDER BY ORDINAL_POSITION
            SEPARATOR ',\n'
        ),
        '\n);'
    ) AS create_statement
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'brooks_obras' 
  AND TABLE_NAME = 'painel_obras_custos_previstos'
GROUP BY TABLE_NAME;
