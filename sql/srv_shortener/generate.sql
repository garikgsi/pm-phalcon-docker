
explain analyse
with smb AS (
    SELECT res.symbol_position
         , res.letter
    FROM (
             SELECT lbs.symbol_position
                  , jsonb_array_elements_text(lss.set_symbols) AS letter
             FROM srv_shortener.link_block_set AS lbs
                , srv_shortener.link_symbol_set AS lss
             WHERE lbs.link_block_id = 566577
               AND lbs.symbol_position <> 1
               AND lss.link_symbol_set_id = lbs.link_symbol_set_id
         )  AS res
)
SELECT res.uri
FROM (
         SELECT CONCAT('h', s1.letter, s2.letter, s3.letter, s4.letter, s5.letter) AS uri
         FROM smb AS s1
            , smb AS s2
            , smb AS s3
            , smb AS s4
            , smb AS s5
         WHERE s1.symbol_position = 2
           AND s2.symbol_position = 3
           AND s3.symbol_position = 4
           AND s4.symbol_position = 5
           AND s5.symbol_position = 6
             EXCEPT
         SELECT lr.uri_name
         FROM srv_shortener.link_uri AS lr
         WHERE lr.link_block_id = 566577
           AND lr.segment_first_letter = 'h'
     ) res
WHERE random() < 0.01
LIMIT 1;


explain analyze
with blk AS (
    select lb.link_block_id
    from srv_shortener.link_block AS lb
    WHERE lb.link_type_id = 3
      AND lb.block_size_total <> lb.block_size_used
      AND random() < 0.01
    LIMIT 10
)
SELECT res.link_block_id
     , res.segment_first_letter
FROM (
         SELECT lbs.link_block_id
              , lbs.segment_first_letter
              , ROW_NUMBER() OVER(PARTITION BY lbs.link_block_id ORDER BY lbs.segment_size_total - lbs.segment_size_used DESC, random()) AS rank
         FROM srv_shortener.link_block_segment AS lbs
            , blk
         WHERE lbs.link_block_id = blk.link_block_id
     ) AS res
WHERE res.rank = 1;


