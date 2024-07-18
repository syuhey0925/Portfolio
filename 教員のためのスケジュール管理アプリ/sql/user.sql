DROP TABLE IF EXISTS USER;
CREATE TABLE USER (
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_teacher BOOLEAN NOT NULL,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (email)
);

INSERT INTO USER (email, password, is_teacher, name)
VALUES
    ('user1@example.com', 'password1', true, 'User 1'),
    ('user2@example.com', 'password2', true, 'User 2'),
    ('user3@example.com', 'password3', true, 'User 3'),
    ('user4@example.com', 'password4', true, 'User 4'),
    ('user5@example.com', 'password5', true, 'User 5'),
    ('user6@example.com', 'password6', true, 'User 6'),
    ('user7@example.com', 'password7', true, 'User 7'),
    ('user8@example.com', 'password8', true, 'User 8'),
    ('user9@example.com', 'password9', true, 'User 9'),
    ('user10@example.com', 'password10', true, 'User 10'),
    ('user11@example.com', 'password11', true, 'User 11'),
    ('user12@example.com', 'password12', true, 'User 12'),
    ('user13@example.com', 'password13', true, 'User 13'),
    ('user14@example.com', 'password14', true, 'User 14'),
    ('user15@example.com', 'password15', true, 'User 15'),
    ('user16@example.com', 'password16', false, 'User 16'),
    ('user17@example.com', 'password17', false, 'User 17'),
    ('user18@example.com', 'password18', false, 'User 18'),
    ('user19@example.com', 'password19', false, 'User 19'),
    ('user20@example.com', 'password20', false, 'User 20');