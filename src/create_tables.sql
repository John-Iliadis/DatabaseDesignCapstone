create table Championship
(
    ChampionshipID smallint unsigned auto_increment,
    ChampionshipName varchar(100),
    ChampionshipYear year unique,
    primary key (ChampionshipID)
);

create table Competition
(
    CompetitionID smallint unsigned auto_increment,
    ChampionshipID smallint unsigned,
    CompetitionName varchar(100),
    CompetitionDate date,
    primary key (CompetitionID),
    foreign key (ChampionshipID) references Championship(ChampionshipID)
);

create table Round
(
    RoundCode varchar(20),
    primary key (RoundCode)
);

create table `Range`
(
    RangeID int unsigned auto_increment,
    RoundCode varchar(20),
    Distance enum('90', '70', '60', '50', '40', '30', '20', '10'),
    EndCount enum('5', '6'),
    FaceSize enum('80', '122'),
    primary key (RangeID),
    foreign key (RoundCode) references Round(RoundCode)
);

create table EquivalentRound
(
    RoundCode varchar(20),
    EquivalentRoundCode varchar(20),
    Valid bit(1),
    primary key (RoundCode, EquivalentRoundCode),
    foreign key (RoundCode) references Round(RoundCode)
        on update cascade,
    foreign key (EquivalentRoundCode) references Round(RoundCode)
        on update cascade
);

create table Archer
(
    ArcherID int unsigned auto_increment,
    FirstName varchar(50),
    LastName varchar(50),
    ArcherAge tinyint unsigned,
    Gender enum('M', 'F'),
    primary key (ArcherID)
);

create table Class
(
    ClassName varchar(100),
    AgeLimitMin tinyint unsigned,
    AgeLimitMax tinyint unsigned,
    Gender enum('M', 'F'),
    primary key (ClassName)
);

create table Category
(
    CategoryName varchar(100),
    ClassName varchar(100),
    Division enum('Recurve', 'Barebow', 'Longbow', 'Compound'),
    primary key (CategoryName),
    foreign key (ClassName) references Class(ClassName)
);

create table RoundCategory
(
    RoundCode varchar(20),
    CategoryName varchar(100),
    primary key (RoundCode, CategoryName),
    foreign key (RoundCode) references Round(RoundCode)
        on update cascade,
    foreign key (CategoryName) references Category(CategoryName)
        on update cascade
);

create table RoundResult
(
    RoundResultID int unsigned auto_increment,
    ArcherID int unsigned,
    RoundCode varchar(20),
    CategoryName varchar(100),
    CompetitionID smallint unsigned default null,
    Result int unsigned,
    ResultDate date,
    primary key (RoundResultID),
    foreign key (ArcherID) references Archer(ArcherID),
    foreign key (RoundCode) references Round(RoundCode)
        on update cascade,
    foreign key (CategoryName) references Category(CategoryName)
        on update cascade,
    foreign key (CompetitionID) references Competition(CompetitionID)
);

create table Score
(
    ScoreID int unsigned auto_increment,
    RoundResultID int unsigned,
    RangeIndex tinyint unsigned,
    EndIndex tinyint unsigned,
    Arrow1 tinyint unsigned,
    Arrow2 tinyint unsigned,
    Arrow3 tinyint unsigned,
    Arrow4 tinyint unsigned,
    Arrow5 tinyint unsigned,
    Arrow6 tinyint unsigned,
    primary key (ScoreID),
    foreign key (RoundResultID) references RoundResult(RoundResultID)
);
