import csv
from datetime import datetime

# ファイルを開く
with open('data/schedules.csv', 'r') as f:
    reader = csv.reader(f)

    sql = 'DROP TABLE IF EXISTS SCHEDULE;\nCREATE TABLE SCHEDULE (\n\tdate DATE NOT NULL,\n\tperiod INT NOT NULL,\n\tclass VARCHAR(10) NOT NULL,\n\tteacher_email VARCHAR(255) NOT NULL,\n\tsubject VARCHAR(10) NOT NULL,\n\tteacher_name VARCHAR(50) NOT NULL,\n\ttotal INT NOT NULL,\n\texam BOOLEAN NOT NULL,\n\tcomment TEXT,\n\tPRIMARY KEY (date, period, class, teacher_email, subject)\n);\n\nINSERT INTO SCHEDULE (date, period, class, teacher_email, subject, teacher_name, total, exam)\nVALUES\n'

    for row in reader:
        if reader.line_num == 1:
            continue

        date_str = row[0]
        date_obj = datetime.strptime(date_str, '%Y/%m/%d')
        formatted_date = date_obj.strftime('%Y-%m-%d')
        exam_value = bool(int(row[6]))
        
        sql += f"\t('{formatted_date}', {row[1]}, '{row[2]}', '{row[3]}', '{row[4]}', (SELECT name FROM USER WHERE email = '{row[3]}'), {row[5]}, {exam_value}),\n"

sql = sql.rstrip(',\n') + ';'
sql += '\nUPDATE SCHEDULE SET comment = \'\' WHERE comment IS NULL;\n\nDROP TABLE IF EXISTS SCHEDULE_LIST;\nCREATE TABLE SCHEDULE_LIST (\n\tname VARCHAR(255) NOT NULL,\n\tis_class BOOLEAN NOT NULL\n);\n\nINSERT INTO SCHEDULE_LIST (name, is_class) SELECT DISTINCT class, TRUE FROM SCHEDULE;\nINSERT INTO SCHEDULE_LIST (name, is_class) SELECT DISTINCT teacher_email, FALSE FROM SCHEDULE;'

with open('../schedule.sql', 'w') as output_file:
    output_file.write(sql)