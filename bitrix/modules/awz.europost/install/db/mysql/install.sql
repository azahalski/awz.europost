CREATE TABLE IF NOT EXISTS `b_awz_europost_pvz` (
    ID int(18) NOT NULL AUTO_INCREMENT,
    PVZ_ID varchar(255) NOT NULL,
    TOWN varchar(65) NOT NULL,
    PRM varchar(6255) DEFAULT NULL,
    PRIMARY KEY (`ID`),
    unique IX_PVZ_ID (PVZ_ID),
    index IX_TOWN (TOWN)
);