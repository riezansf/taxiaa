CREATE
OR REPLACE FUNCTION join_gps_argo () RETURNS void AS $BODY$
DECLARE argo CURSOR FOR SELECT
	no_taxi,
	date,
	start_trip,
	end_trip
FROM
	trip_argo_12;

a_taxi VARCHAR;

a_date date;

a_start time;

a_end time;

gps CURSOR FOR SELECT
	no_taxi,
	TIMESTAMP,
	lat,
	LONG
FROM
	GPS_2015_12
WHERE
	no_taxi = a_taxi
AND TIMESTAMP :: date = a_date;

g_taxi VARCHAR;

g_time TIMESTAMP;

g_lat FLOAT;

g_long FLOAT;


BEGIN
	OPEN argo;


LOOP
	FETCH argo INTO a_taxi,
	a_date,
	a_start,
	a_end;

EXIT
WHEN NOT found;

OPEN gps;


LOOP
	FETCH gps INTO g_taxi,
	g_time,
	g_lat,
	g_long;

EXIT
WHEN NOT found;


IF a_start = g_time THEN
	UPDATE argo_gps_join_12
SET pickup_loc_lat = g_lat,
 pickup_loc_long = g_long
WHERE
	date = a_date
AND no_taxi = a_taxi
AND start_trip = a_start
AND end_trip = a_end;

ELSIF a_end = g_time THEN
	UPDATE argo_gps_join_12
SET dropoff_loc_lat = g_lat,
 dropoff_loc_long = g_long,

WHERE
	date = a_date
AND no_taxi = a_taxi
AND start_trip = a_start
AND end_trip = a_end;


END
IF;


END
LOOP
;

CLOSE gps;


END
LOOP
;

CLOSE argo;


END;

$BODY$ LANGUAGE plpgsql VOLATILE;