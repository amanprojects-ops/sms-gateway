Imports System
Imports System.Net
Imports System.IO
Imports System.Text
Imports System.Web
Imports Newtonsoft.Json
Imports Newtonsoft.Json.Linq

''' <summary>
''' SMS Gateway VB.NET SDK
''' 
''' A simple VB.NET SDK to interact with the SMS Gateway API
''' 
''' @version 1.0.0
''' @author SMS Gateway
''' @license MIT
''' </summary>
Public Class SMSGateway
    Private _apiKey As String
    Private _baseUrl As String
    Private _debug As Boolean
    Private _lastError As String
    Private _lastResponse As JObject

    ''' <summary>
    ''' Constructor with default base URL
    ''' </summary>
    ''' <param name="apiKey">Your API key</param>
    Public Sub New(apiKey As String)
        Me.New(apiKey, "https://yourdomain.com/api", False)
    End Sub

    ''' <summary>
    ''' Constructor with custom base URL
    ''' </summary>
    ''' <param name="apiKey">Your API key</param>
    ''' <param name="baseUrl">Base URL for the API</param>
    Public Sub New(apiKey As String, baseUrl As String)
        Me.New(apiKey, baseUrl, False)
    End Sub

    ''' <summary>
    ''' Constructor with custom base URL and debug option
    ''' </summary>
    ''' <param name="apiKey">Your API key</param>
    ''' <param name="baseUrl">Base URL for the API</param>
    ''' <param name="debug">Enable debug mode</param>
    Public Sub New(apiKey As String, baseUrl As String, debug As Boolean)
        _apiKey = apiKey
        _baseUrl = baseUrl.TrimEnd("/"c)
        _debug = debug
    End Sub

    ''' <summary>
    ''' Get the last error message
    ''' </summary>
    ''' <returns>Last error message</returns>
    Public Function GetLastError() As String
        Return _lastError
    End Function

    ''' <summary>
    ''' Get the last API response
    ''' </summary>
    ''' <returns>Last API response as JObject</returns>
    Public Function GetLastResponse() As JObject
        Return _lastResponse
    End Function

    ''' <summary>
    ''' Send an SMS message
    ''' </summary>
    ''' <param name="to">Recipient phone number in international format (e.g., +1234567890)</param>
    ''' <param name="message">Message content</param>
    ''' <returns>Response as JObject or Nothing on failure</returns>
    Public Function SendSMS(to As String, message As String) As JObject
        Dim parameters As New Dictionary(Of String, String)()
        parameters.Add("to", to)
        parameters.Add("message", message)
        
        Return MakeRequest("send_sms.php", parameters)
    End Function

    ''' <summary>
    ''' Generate and send an OTP
    ''' </summary>
    ''' <param name="phone">Recipient phone number in international format (e.g., +1234567890)</param>
    ''' <param name="template">Optional message template with {otp} placeholder</param>
    ''' <returns>Response as JObject or Nothing on failure</returns>
    Public Function GenerateOTP(phone As String, Optional template As String = "{otp}") As JObject
        Dim parameters As New Dictionary(Of String, String)()
        parameters.Add("phone", phone)
        parameters.Add("template", template)
        
        Return MakeRequest("generate_otp.php", parameters)
    End Function

    ''' <summary>
    ''' Verify an OTP
    ''' </summary>
    ''' <param name="phone">Phone number in international format (e.g., +1234567890)</param>
    ''' <param name="otp">OTP code to verify</param>
    ''' <returns>Response as JObject or Nothing on failure</returns>
    Public Function VerifyOTP(phone As String, otp As String) As JObject
        Dim parameters As New Dictionary(Of String, String)()
        parameters.Add("phone", phone)
        parameters.Add("otp", otp)
        
        Return MakeRequest("verify_otp.php", parameters)
    End Function

    ''' <summary>
    ''' Calculate how many SMS parts a message will require
    ''' </summary>
    ''' <param name="message">Message content</param>
    ''' <returns>Number of SMS parts</returns>
    Public Function CalculateSMSParts(message As String) As Integer
        Dim length As Integer = message.Length
        
        ' Check if message contains non-GSM characters
        Dim containsUnicode As Boolean = False
        For i As Integer = 0 To length - 1
            If AscW(message(i)) > 127 Then
                containsUnicode = True
                Exit For
            End If
        Next
        
        ' Unicode messages have different length limits
        Dim maxLength As Integer
        If containsUnicode Then
            maxLength = 70
        Else
            maxLength = 160
        End If
        
        ' Calculate parts
        If length <= maxLength Then
            Return 1
        Else
            ' For multi-part messages, each part is smaller due to header info
            Dim charPerPart As Integer = If(containsUnicode, 67, 153)
            Return Math.Ceiling(length / charPerPart)
        End If
    End Function

    ''' <summary>
    ''' Make an API request
    ''' </summary>
    ''' <param name="endpoint">API endpoint</param>
    ''' <param name="parameters">Request parameters</param>
    ''' <returns>Response as JObject or Nothing on failure</returns>
    Private Function MakeRequest(endpoint As String, parameters As Dictionary(Of String, String)) As JObject
        Try
            Dim url As String = $"{_baseUrl}/{endpoint}"
            
            ' Create web request
            Dim request As HttpWebRequest = DirectCast(WebRequest.Create(url), HttpWebRequest)
            request.Method = "POST"
            request.ContentType = "application/x-www-form-urlencoded"
            request.Headers.Add("X-API-Key", _apiKey)
            
            ' Prepare request data
            Dim postData As New StringBuilder()
            For Each param In parameters
                If postData.Length > 0 Then
                    postData.Append("&")
                End If
                postData.Append($"{HttpUtility.UrlEncode(param.Key)}={HttpUtility.UrlEncode(param.Value)}")
            Next
            
            Dim data As Byte() = Encoding.UTF8.GetBytes(postData.ToString())
            request.ContentLength = data.Length
            
            ' Debug output
            If _debug Then
                Console.WriteLine($"Request URL: {url}")
                Console.WriteLine($"Request data: {postData}")
            End If
            
            ' Send request
            Using requestStream As Stream = request.GetRequestStream()
                requestStream.Write(data, 0, data.Length)
            End Using
            
            ' Get response
            Using response As HttpWebResponse = DirectCast(request.GetResponse(), HttpWebResponse)
                Using reader As New StreamReader(response.GetResponseStream())
                    Dim responseText As String = reader.ReadToEnd()
                    
                    ' Debug output
                    If _debug Then
                        Console.WriteLine($"Response: {responseText}")
                    End If
                    
                    ' Parse JSON response
                    _lastResponse = JObject.Parse(responseText)
                    
                    ' Check for error
                    If _lastResponse.ContainsKey("error") Then
                        _lastError = _lastResponse("error")("message").ToString()
                        Return Nothing
                    End If
                    
                    Return _lastResponse
                End Using
            End Using
        Catch ex As WebException
            ' Handle HTTP errors
            Using response As HttpWebResponse = DirectCast(ex.Response, HttpWebResponse)
                If response IsNot Nothing Then
                    Using reader As New StreamReader(response.GetResponseStream())
                        Dim responseText As String = reader.ReadToEnd()
                        
                        ' Debug output
                        If _debug Then
                            Console.WriteLine($"Error response: {responseText}")
                        End If
                        
                        Try
                            ' Try to parse error response
                            _lastResponse = JObject.Parse(responseText)
                            _lastError = If(_lastResponse.ContainsKey("error"), 
                                           _lastResponse("error")("message").ToString(), 
                                           "Unknown error")
                        Catch jsonEx As Exception
                            _lastError = $"HTTP Error: {CInt(response.StatusCode)} {response.StatusDescription}"
                        End Try
                    End Using
                Else
                    _lastError = ex.Message
                End If
            End Using
        Catch ex As Exception
            ' Handle other errors
            _lastError = ex.Message
            If _debug Then
                Console.WriteLine($"Exception: {ex}")
            End If
        End Try
        
        Return Nothing
    End Function
End Class
