package main

import (
     "fmt"
     "os"
     "net/http"
     "time"
     "io/ioutil"
     "encoding/json"
)

// a struct to hold the result from each request including an index
// which will be used for sorting the results after they come in
type result struct {
	id int
	status  int
	err   error
}

type check struct {
    // defining struct variables
    Url			string		`json:"url"`
    Id			int		`json:"id"`
    Computers_id	int		`json:"computers_id"`
    Status		string		`json:"status"`
}


func boundedParallelGet(Checks []check, concurrencyLimit int) []check {

	// this buffered channel will block at the concurrency limit
	semaphoreChan := make(chan struct{}, concurrencyLimit)

	// this channel will not block and collect the http request results
	resultsChan := make(chan *result)

	// make sure we close these channels when we're done with them
	defer func() {
		close(semaphoreChan)
		close(resultsChan)
	}()

	// keen an index and loop through every url we will send a request to
	for _,chck := range Checks {

		// start a go routine with the index and url in a closure
		go func(chck check) {
			var status int

			// this sends an empty struct into the semaphoreChan which
			// is basically saying add one to the limit, but when the
			// limit has been reached block until there is room
			semaphoreChan <- struct{}{}

			// send the request and put the response in a result struct
			// along with the index so we can sort them later along with
			// any error that might have occoured
			//fmt.Println(chck.Url)
			client := http.Client{
			   Timeout: 7 * time.Second,
			}

			res, err := client.Get(chck.Url)
			status = 404
			if err == nil {
			  status = res.StatusCode
			}

			result := &result{chck.Id, status, err}
			// now we can send the result struct through the resultsChan
			resultsChan <- result

			// once we're done it's we read from the semaphoreChan which
			// has the effect of removing one from the limit and allowing
			// another goroutine to start
			<-semaphoreChan
		}(chck)
	}

	// make a slice to hold the results we're expecting
	var results []result

	// start listening for any results over the resultsChan
	// once we get a result append it to the result slice
	for {
		result := <-resultsChan
		results = append(results, *result)

		// if we've reached the expected amount of urls then stop
		if len(results) == len(Checks) {
			break
		}
	}

	var ret []check
	for i := range results {
		ch := m[results[i].id]
		if results[i].status == 200 {
			ch.Status = "OK"
			ret = append(ret,*ch)
		}
	}
	// now we're done we return the results
	return ret
}

var checks []check
var m = make(map[int]*check)
func init() {

	bytes, _ := ioutil.ReadAll(os.Stdin)
	json.Unmarshal(bytes , &checks)
	for i := range checks {
	   m[checks[i].Id] = &checks[i]
	}
}

func main() {
		//startTime := time.Now()
		results := boundedParallelGet(checks, 100)
		//seconds := time.Since(startTime).Seconds()
		//tmplate := "requests: %d/%d in %v"

	//	fmt.Printf(tmplate, len(results), len(checks), seconds)

		val,_ := json.MarshalIndent(results, "", "    ")
		fmt.Print(string(val))
}

