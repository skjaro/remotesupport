BINARY_NAME=check_status
GO_PRG=check_status.go
BIN_DIR=./bin
GO=/usr/local/go/bin/go

all: build test

build:
	${GO} build -o ${BIN_DIR}/${BINARY_NAME} ${GO_PRG}

test:
	${GO} test -v ${GO_PRG}

clean:
	${GO} clean
	rm ${BIN_DIR}/${BINARY_NAME}
